<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Package;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

/**
 * Turns a paid order into what the customer bought. Safe to call twice:
 * an order that is already paid is not fulfilled again.
 */
class OrderService
{
    public static function markPaid(Order $order, string $method, array $payload = []): void
    {
        DB::transaction(function () use ($order, $method, $payload) {
            $order = Order::lockForUpdate()->find($order->id);
            if ($order->isPaid()) {
                return;
            }
            $order->update([
                'status' => 'paid',
                'method' => $method,
                'paid_at' => now(),
                'gateway_payload' => $payload ?: $order->gateway_payload,
            ]);
            self::fulfil($order);
        });
    }

    private static function fulfil(Order $order): void
    {
        $meta = $order->meta ?? [];

        switch ($order->type) {
            case 'subscription':
                $package = Package::findOrFail($meta['package_id']);
                $user = $order->user;
                // Extend from the end of the current period when renewing the same package
                $current = $user->activeSubscription;
                $start = ($current && $current->package_id === $package->id) ? $current->ends_at : now();
                if ($current && $current->package_id !== $package->id) {
                    $current->update(['status' => 'cancelled']);
                }
                Subscription::create([
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'order_id' => $order->id,
                    'starts_at' => now(),
                    'ends_at' => $start->copy()->addDays($package->duration_days),
                    'promotions_left' => $package->promotions,
                    'premium_ads_left' => $package->premium_ads,
                    'status' => 'active',
                ]);
                if ($package->stores > 0 && $user->account_type !== 'business') {
                    $user->update(['account_type' => 'business']);
                }
                break;

            case 'promotion':
                $listing = Listing::find($meta['listing_id']);
                if ($listing) {
                    PromotionService::apply($listing, $meta['promotion'], 'order', $order);
                }
                break;

            case 'posting_fee':
                $listing = Listing::find($meta['listing_id']);
                if ($listing && $listing->status === 'pending_payment') {
                    ListingPublisher::publishAfterPayment($listing);
                }
                break;

            case 'banner':
                $banner = Banner::find($meta['banner_id']);
                if ($banner) {
                    // Paid banners still need a quick look at the artwork before they go live
                    $banner->update(['status' => 'pending', 'order_id' => $order->id]);
                }
                break;
        }
    }
}
