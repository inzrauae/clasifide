<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\User;

/**
 * Decides what status a freshly posted/edited ad gets: live, held for review,
 * or waiting for the premium posting fee (Property & Vehicle ads).
 */
class ListingPublisher
{
    /** Does this user owe the premium posting fee for an ad in this category? */
    public static function feeDue(Listing $listing, User $user): int
    {
        $category = $listing->category;
        if (! $category || $category->posting_fee <= 0) {
            return 0;
        }

        // Package credit for premium ads
        $sub = $user->activeSubscription;
        if ($sub && $sub->premium_ads_left > 0) {
            return 0;
        }

        // Monthly free quota for this category
        if ($category->free_ads_per_month > 0) {
            $used = Listing::withTrashed()->where('user_id', $user->id)
                ->where('category_id', $category->id)
                ->where('created_at', '>=', now()->startOfMonth())
                ->when($listing->exists, fn ($q) => $q->whereKeyNot($listing->id))
                ->count();
            if ($used < $category->free_ads_per_month) {
                return 0;
            }
        }

        return $category->posting_fee;
    }

    /** Use a package premium-ad credit, if that is how the fee was waived. */
    public static function consumePremiumCredit(Listing $listing, User $user): void
    {
        $category = $listing->category;
        $sub = $user->activeSubscription;
        if ($category && $category->posting_fee > 0 && $sub && $sub->premium_ads_left > 0) {
            $sub->decrement('premium_ads_left');
        }
    }

    public static function decideStatus(Listing $listing, User $user): void
    {
        $risk = SafetyService::assess($listing, $user);
        $listing->risk_score = $risk['score'];

        $reasons = $risk['reasons'];
        if ($listing->exists && SafetyService::reusedPhotos($listing)) {
            $reasons[] = 'Photos used in another seller\'s ad';
            $listing->risk_score = min(100, $listing->risk_score + 30);
        }

        $listing->is_flagged = $listing->risk_score >= config('marketplace.safety.review_threshold');
        $listing->flag_reason = $reasons ? implode('; ', $reasons) : null;

        if ($listing->is_flagged || ! config('marketplace.auto_approve')) {
            $listing->status = 'pending';
        } else {
            $listing->status = 'active';
            $listing->published_at ??= now();
            $listing->expires_at = now()->addDays(config('marketplace.ad_lifetime_days'));
        }
    }

    public static function publishAfterPayment(Listing $listing): void
    {
        $user = $listing->user;
        self::decideStatus($listing, $user);
        $listing->save();
    }
}
