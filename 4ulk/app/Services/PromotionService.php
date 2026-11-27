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

    /**
     * The lengths a seller can choose for a promotion, shortest first:
     * [3 => ['days' => 3, 'price' => 250, 'credits' => 1], 7 => [...], ...]. Bump has one "one time" option.
     */
    public static function options(string $type): array
    {
        $cfg = self::types()[$type];
        $opts = $cfg['options'] ?? [(int) $cfg['days'] => ['price' => $cfg['price'], 'credits' => $cfg['credits']]];
        ksort($opts);

        return collect($opts)->map(fn ($o, $days) => ['days' => (int) $days, 'price' => (int) $o['price'], 'credits' => (int) $o['credits']])->all();
    }

    /** One option for a promotion; the default length when $days is not given or not offered. */
    public static function option(string $type, ?int $days = null): array
    {
        $opts = self::options($type);

        return $opts[$days] ?? $opts[(int) self::types()[$type]['days']] ?? reset($opts);
    }

    /** Apply a promotion to an ad, extending any time it already has. */
    public static function apply(Listing $listing, string $type, string $paidWith, ?Order $order = null, ?int $days = null): ListingPromotion
    {
        $cfg = self::types()[$type];
        $cfg['days'] = self::option($type, $days)['days'];
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
