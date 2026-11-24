<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingStat;
use App\Models\Order;
use Illuminate\Support\Collection;

/**
 * Numbers about one category (ad type) shared by the admin and seller screens:
 * prices, what the live ads are made of, buyer activity and premium-fee income.
 */
class CategoryInsights
{
    /** Price spread of live ads with a price, optionally in one district. */
    public static function prices(Category $category, ?string $district = null): array
    {
        $prices = Listing::live()->where('category_id', $category->id)
            ->when($district, fn ($q) => $q->where('district', $district))
            ->where('price', '>', 0)->orderBy('price')->pluck('price');

        return [
            'count' => $prices->count(),
            'median' => self::median($prices),
            'low' => $prices->first(),
            'high' => $prices->last(),
        ];
    }

    public static function median(Collection $sorted): ?int
    {
        $n = $sorted->count();
        if (! $n) {
            return null;
        }
        $mid = intdiv($n, 2);

        return (int) ($n % 2 ? $sorted[$mid] : round(($sorted[$mid - 1] + $sorted[$mid]) / 2));
    }

    /**
     * What live ads in this category are made of, field by field, e.g.
     * ['Fuel type' => ['Petrol' => 40, 'Hybrid' => 22], 'Brand' => ['Toyota' => 31, ...]].
     * Free-text fields only count values that repeat, so one-off answers (mileage, model) are skipped.
     */
    public static function specBreakdown(Category $category, int $top = 6): array
    {
        $fields = $category->specFields();
        if (! $fields) {
            return [];
        }

        $counts = [];
        Listing::live()->where('category_id', $category->id)->whereNotNull('specs')
            ->select(['id', 'specs'])->chunkById(1000, function ($ads) use ($fields, &$counts) {
                foreach ($ads as $ad) {
                    foreach ($fields as $f) {
                        $v = trim((string) ($ad->specs[$f['name']] ?? ''));
                        if ($v === '') {
                            continue;
                        }
                        $key = $f['type'] === 'select' ? $v : mb_convert_case(mb_strtolower($v), MB_CASE_TITLE);
                        $counts[$f['name']][$key] = ($counts[$f['name']][$key] ?? 0) + 1;
                    }
                }
            });

        $out = [];
        foreach ($fields as $f) {
            $values = collect($counts[$f['name']] ?? [])
                ->when($f['type'] !== 'select', fn ($c) => $c->filter(fn ($n) => $n >= 2))
                ->sortDesc()->take($top);
            if ($values->isNotEmpty()) {
                $out[$f['name']] = $values->all();
            }
        }

        return $out;
    }

    /**
     * Views and buyer contacts on ads in this category over the last N days,
     * site-wide or for one seller's ads only.
     */
    public static function activity(Category $category, int $days = 30, ?int $userId = null): array
    {
        $row = ListingStat::join('listings', 'listings.id', '=', 'listing_stats.listing_id')
            ->where('listings.category_id', $category->id)
            ->when($userId, fn ($q) => $q->where('listings.user_id', $userId))
            ->where('listing_stats.date', '>=', now()->subDays($days - 1)->toDateString())
            ->selectRaw('coalesce(sum(listing_stats.views), 0) as views, coalesce(sum(listing_stats.phone_reveals + listing_stats.whatsapp_clicks + listing_stats.messages), 0) as contacts')
            ->first();

        return ['views' => (int) $row->views, 'contacts' => (int) $row->contacts];
    }

    /** Live ad count per district, busiest first. */
    public static function districts(Category $category, int $top = 8): array
    {
        return Listing::live()->where('category_id', $category->id)
            ->selectRaw('district, count(*) as n')->groupBy('district')
            ->orderByDesc('n')->limit($top)->pluck('n', 'district')->all();
    }

    /** Paid premium posting fees collected for ads in this category. */
    public static function feeRevenue(Category $category): array
    {
        $ids = Listing::withTrashed()->where('category_id', $category->id)->pluck('id')->flip();
        $orders = Order::where('type', 'posting_fee')->where('status', 'paid')->get(['amount', 'meta', 'paid_at'])
            ->filter(fn ($o) => isset($ids[$o->meta['listing_id'] ?? null]));
        $month = $orders->filter(fn ($o) => $o->paid_at && $o->paid_at->gte(now()->startOfMonth()));

        return [
            'month' => (int) $month->sum('amount'),
            'month_count' => $month->count(),
            'total' => (int) $orders->sum('amount'),
            'total_count' => $orders->count(),
        ];
    }

    /**
     * Where a seller stands on this category's premium fee this month:
     * free quota used, package credits left, and what the next ad will cost.
     */
    public static function feeStatus(Category $category, $user): ?array
    {
        if (! $category->isPremium()) {
            return null;
        }

        $used = Listing::withTrashed()->where('user_id', $user->id)->where('category_id', $category->id)
            ->where('created_at', '>=', now()->startOfMonth())->count();
        $freeLeft = max(0, $category->free_ads_per_month - $used);
        $credits = (int) ($user->activeSubscription?->premium_ads_left ?? 0);

        return [
            'fee' => $category->posting_fee,
            'free' => $category->free_ads_per_month,
            'used' => $used,
            'free_left' => $freeLeft,
            'credits' => $credits,
            'next_costs' => ($credits > 0 || $freeLeft > 0) ? 0 : $category->posting_fee,
        ];
    }
}
