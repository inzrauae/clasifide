<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Message;
use App\Services\SafetyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $box = $request->query('box', 'all'); // all | buying | selling | archived

        $conversations = Conversation::with(['listing.images', 'buyer', 'seller', 'latestMessage'])
            ->where(function ($q) use ($user, $box) {
                if ($box === 'archived') {
                    $q->where(fn ($w) => $w->where('buyer_id', $user->id)->where('buyer_archived', true))
                        ->orWhere(fn ($w) => $w->where('seller_id', $user->id)->where('seller_archived', true));

                    return;
                }
                if ($box !== 'selling') {
                    $q->orWhere(fn ($w) => $w->where('buyer_id', $user->id)->where('buyer_archived', false));
                }
                if ($box !== 'buying') {
                    $q->orWhere(fn ($w) => $w->where('seller_id', $user->id)->where('seller_archived', false));
                }
            })
            ->orderByDesc('last_message_at')
            ->paginate(25)->withQueryString();

        return view('messages.index', compact('conversations', 'box'));
    }

    /** Buyer opens (or re-opens) a chat about an ad. */
    public function start(Request $request, Listing $listing)
    {
        $user = $request->user();
        abort_unless($listing->isLive(), 404);
        if ($listing->user_id === $user->id) {
            return back()->with('error', 'This is your own ad.');
        }

        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:1500']]);

        $conversation = Conversation::where('listing_id', $listing->id)->where('buyer_id', $user->id)->first();

        if (! $conversation) {
            $limit = config('marketplace.safety.new_conversations_per_day');
            if (Conversation::where('buyer_id', $user->id)->where('created_at', '>', now()->subDay())->count() >= $limit) {
                return back()->with('error', 'You have started a lot of chats today. Please try again tomorrow.');
            }
            $conversation = Conversation::create([
                'listing_id' => $listing->id,
                'buyer_id' => $user->id,
                'seller_id' => $listing->user_id,
            ])->fresh(); // load column defaults (lead_status, unread counters)
        }

        $error = $this->send($conversation, $user, $data['body']);
        if ($error) {
            return back()->withInput()->with('error', $error);
        }

        return redirect()->route('messages.show', $conversation);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        abort_unless($conversation->involves($user), 403);

        // Mark the other party's messages as read
        $conversation->messages()->where('sender_id', '!=', $user->id)->whereNull('read_at')->update(['read_at' => now()]);
        $conversation->update([$conversation->roleOf($user).'_unread' => 0]);

        $conversation->load(['listing.images', 'buyer', 'seller', 'messages']);

        return view('messages.show', [
            'conversation' => $conversation,
            'other' => $conversation->otherParty($user),
            'role' => $conversation->roleOf($user),
            'leadTools' => $user->hasLeadTools() || $user->isAdmin(),
        ]);
    }

    public function reply(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        abort_unless($conversation->involves($user), 403);
        $data = $request->validate(['body' => ['required', 'string', 'min:1', 'max:1500']]);

        if ($error = $this->send($conversation, $user, $data['body'])) {
            return back()->withInput()->with('error', $error);
        }

        return redirect()->to(route('messages.show', $conversation).'#bottom');
    }

    /** Inquiry management: lead status, private note, archive, block. */
    public function update(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        abort_unless($conversation->involves($user), 403);
        $role = $conversation->roleOf($user);

        $data = $request->validate([
            'lead_status' => ['nullable', Rule::in(array_keys(config('marketplace.lead_statuses')))],
            'seller_note' => ['nullable', 'string', 'max:1000'],
            'archive' => ['nullable', 'boolean'],
            'block' => ['nullable', 'boolean'],
        ]);

        $changes = [];
        if ($request->has('archive')) {
            $changes[$role.'_archived'] = (bool) $data['archive'];
        }
        if ($role === 'seller') {
            if ($request->has('lead_status')) {
                $changes['lead_status'] = $data['lead_status'];
            }
            if ($request->has('seller_note')) {
                $changes['seller_note'] = $data['seller_note'];
            }
            if ($request->has('block')) {
                $changes['blocked'] = (bool) $data['block'];
            }
        }
        $conversation->update($changes);

        return back()->with('status', 'Saved.');
    }

    /** Shared send logic with spam protection. Returns an error message or null. */
    private function send(Conversation $conversation, $user, string $body): ?string
    {
        if ($conversation->blocked) {
            return 'The seller is not accepting messages in this chat.';
        }

        $key = 'msg:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, config('marketplace.safety.messages_per_minute'))) {
            return 'You are sending messages too quickly. Wait a moment and try again.';
        }
        RateLimiter::hit($key, 60);

        // Same text pasted into many chats is a classic spam pattern
        $body = trim($body);
        $copies = Message::where('sender_id', $user->id)->where('body', $body)
            ->where('created_at', '>', now()->subHour())->distinct()->count('conversation_id');
        if ($copies >= 4) {
            return 'This message has already been sent to several sellers. Please write to each seller individually.';
        }

        $flagged = SafetyService::messageLooksSpammy($body);

        $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $body,
            'is_flagged' => $flagged,
        ]);

        $isSeller = $conversation->roleOf($user) === 'seller';
        $conversation->forceFill([
            'last_message_at' => now(),
            ($isSeller ? 'buyer_unread' : 'seller_unread') => ($isSeller ? $conversation->buyer_unread : $conversation->seller_unread) + 1,
            ($isSeller ? 'buyer_archived' : 'seller_archived') => false,
            'lead_status' => $isSeller && $conversation->lead_status === 'new' ? 'contacted' : $conversation->lead_status,
        ])->save();

        if (! $isSeller && $conversation->messages()->count() === 1) {
            $conversation->listing?->track('messages');
        }

        return null;
    }
}
