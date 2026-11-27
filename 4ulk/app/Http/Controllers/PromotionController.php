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
            'days' => ['nullable', 'integer'],
            'pay_with' => ['required', Rule::in(['credit', 'pay'])],
        ]);
        if (isset($data['days']) && ! isset(PromotionService::options($data['type'])[$data['days']])) {
            return back()->with('error', __('Please choose how long to promote your ad.'));
        }
        $cfg = PromotionService::types()[$data['type']];
        $opt = PromotionService::option($data['type'], isset($data['days']) ? (int) $data['days'] : null);

        if (! in_array($listing->status, ['active', 'pending'], true)) {
            return back()->with('error', __('Only live ads can be promoted.'));
        }

        if ($data['pay_with'] === 'credit') {
            $sub = $user->activeSubscription;
            if (! $sub || $sub->promotions_left < $opt['credits']) {
                return back()->with('error', __('Not enough promotion credits. Pay for this promotion or upgrade your package.'));
            }
            $sub->decrement('promotions_left', $opt['credits']);
            PromotionService::apply($listing, $data['type'], 'credit', null, $opt['days']);

            return redirect()->route('dashboard.ads')->with('status', __(':promotion applied using :count credit(s).', ['promotion' => __($cfg['name']), 'count' => $opt['credits']]));
        }

        $order = Order::create([
            'user_id' => $user->id,
            'type' => 'promotion',
            'description' => "{$cfg['name']}".($opt['days'] ? " ({$opt['days']} days)" : '').': '.Str::limit($listing->title, 60),
            'amount' => $opt['price'],
            'meta' => ['listing_id' => $listing->id, 'promotion' => $data['type'], 'days' => $opt['days']],
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
