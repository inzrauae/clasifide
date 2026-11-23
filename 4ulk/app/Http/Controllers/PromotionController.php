<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Order;
use App\Services\PromotionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PromotionController extends Controller
{
    public function show(Request $request, Listing $listing)
    {
        abort_unless($request->user()->id === $listing->user_id, 403);

        return view('listings.promote', [
            'listing' => $listing->load('images', 'category'),
            'promotions' => PromotionService::types(),
            'subscription' => $request->user()->activeSubscription,
        ]);
    }

    public function store(Request $request, Listing $listing)
    {
        $user = $request->user();
        abort_unless($user->id === $listing->user_id, 403);

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(PromotionService::types()))],
            'pay_with' => ['required', Rule::in(['credit', 'pay'])],
        ]);
        $cfg = PromotionService::types()[$data['type']];

        if (! in_array($listing->status, ['active', 'pending'], true)) {
            return back()->with('error', 'Only live ads can be promoted.');
        }

        if ($data['pay_with'] === 'credit') {
            $sub = $user->activeSubscription;
            if (! $sub || $sub->promotions_left < $cfg['credits']) {
                return back()->with('error', 'Not enough promotion credits. Pay for this promotion or upgrade your package.');
            }
            $sub->decrement('promotions_left', $cfg['credits']);
            PromotionService::apply($listing, $data['type'], 'credit');

            return redirect()->route('dashboard.ads')->with('status', "{$cfg['name']} applied using {$cfg['credits']} credit(s).");
        }

        $order = Order::create([
            'user_id' => $user->id,
            'type' => 'promotion',
            'description' => "{$cfg['name']}".($cfg['days'] ? " ({$cfg['days']} days)" : '').': '.Str::limit($listing->title, 60),
            'amount' => $cfg['price'],
            'meta' => ['listing_id' => $listing->id, 'promotion' => $data['type']],
        ]);

        return redirect()->route('checkout.show', $order);
    }

    /** Re-open the checkout for an unpaid premium posting fee. */
    public function payFee(Request $request, Listing $listing)
    {
        abort_unless($request->user()->id === $listing->user_id && $listing->status === 'pending_payment', 404);

        $order = Order::where('user_id', $request->user()->id)->where('type', 'posting_fee')
            ->whereIn('status', ['pending', 'failed', 'awaiting_review'])->get()
            ->first(fn ($o) => ($o->meta['listing_id'] ?? null) === $listing->id);

        $order ??= Order::create([
            'user_id' => $request->user()->id,
            'type' => 'posting_fee',
            'description' => "Premium ad fee – {$listing->category->name}: ".Str::limit($listing->title, 60),
            'amount' => $listing->category->posting_fee,
            'meta' => ['listing_id' => $listing->id],
        ]);

        return redirect()->route('checkout.show', $order);
    }
}
