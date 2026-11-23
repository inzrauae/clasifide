<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ListingPromotion;
use App\Models\Order;

class PromotionService
{
    public static function types(): array
    {
        return config('marketplace.promotions');
    }

    /** Apply a promotion to an ad, extending any time it already has. */
    public static function apply(Listing $listing, string $type, string $paidWith, ?Order $order = null): ListingPromotion
    {
        $cfg = self::types()[$type];
        $now = now();
        $ends = null;

        if ($type === 'bump') {
            $listing->bumped_at = $now;
        } else {
            $column = $type.'_until';
            $from = $listing->{$column} && $listing->{$column}->isFuture() ? $listing->{$column} : $now;
            $ends = $from->copy()->addDays($cfg['days']);
            $listing->{$column} = $ends;
            // Promotions also refresh the position in "latest"
            $listing->bumped_at = $now;
        }
        $listing->save();

        return ListingPromotion::create([
            'listing_id' => $listing->id,
            'order_id' => $order?->id,
            'type' => $type,
            'paid_with' => $paidWith,
            'starts_at' => $now,
            'ends_at' => $ends,
        ]);
    }
}
