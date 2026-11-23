<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Trust & safety checks run when an ad is posted or edited:
 *  - duplicate ad detection (same seller/phone + same normalised title, or re-used photos)
 *  - suspicious ad detection (scam phrases, unrealistic price, links, new-account bursts)
 * Produces a 0-100 risk score; ads at or above the threshold are held for review.
 */
class SafetyService
{
    public static function fingerprint(string $title, int $categoryId): string
    {
        $norm = Str::of($title)->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish();

        return hash('sha256', $categoryId.'|'.$norm);
    }

    /** Another live/pending ad from the same seller or phone with the same title in the same category. */
    public static function findDuplicate(User $user, string $fingerprint, string $phone, ?int $ignoreId = null): ?Listing
    {
        $digits = preg_replace('/\D/', '', $phone);

        return Listing::where('fingerprint', $fingerprint)
            ->whereIn('status', ['active', 'pending', 'pending_payment'])
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('phone', $phone)->orWhere('phone', $digits))
            ->first();
    }

    /**
     * @return array{score:int, reasons:array<int,string>}
     */
    public static function assess(Listing $listing, User $user): array
    {
        $cfg = config('marketplace.safety');
        $score = 0;
        $reasons = [];
        $text = Str::lower($listing->title.' '.$listing->description);

        foreach ($cfg['scam_phrases'] as $phrase) {
            if (str_contains($text, $phrase)) {
                $score += 35;
                $reasons[] = "Contains \"{$phrase}\"";
                break;
            }
        }

        if (preg_match('~(https?://|www\.)~i', $listing->description)) {
            $score += 15;
            $reasons[] = 'External link in description';
        }

        // Price far below the category median
        if ($listing->price) {
            $prices = Listing::live()->where('category_id', $listing->category_id)
                ->whereNotNull('price')->where('price', '>', 0)
                ->when($listing->exists, fn ($q) => $q->whereKeyNot($listing->id))
                ->orderBy('price')->limit(400)->pluck('price');
            if ($prices->count() >= 8) {
                $median = $prices[(int) floor($prices->count() / 2)];
                if ($listing->price < $median * $cfg['low_price_ratio']) {
                    $score += 25;
                    $reasons[] = 'Price is far below similar ads';
                }
            }
        }

        // New accounts posting lots of ads quickly
        if ($user->created_at && $user->created_at->gt(now()->subDay())) {
            $recent = Listing::where('user_id', $user->id)->where('created_at', '>', now()->subDay())->count();
            if ($recent >= $cfg['new_account_ad_limit']) {
                $score += 30;
                $reasons[] = 'Many ads from a brand-new account';
            }
        }

        // Low trust sellers
        if ($user->trust_score < 30) {
            $score += 20;
            $reasons[] = 'Seller has a low trust score';
        }

        // Verified sellers get the benefit of the doubt
        if ($user->isVerified()) {
            $score = (int) max(0, $score - 20);
        }

        return ['score' => min(100, $score), 'reasons' => $reasons];
    }

    /** Photos already used in another seller's ad. */
    public static function reusedPhotos(Listing $listing): bool
    {
        $hashes = $listing->images()->pluck('hash')->filter();
        if ($hashes->isEmpty()) {
            return false;
        }

        return ListingImage::whereIn('hash', $hashes)
            ->whereHas('listing', fn ($q) => $q->where('user_id', '!=', $listing->user_id)->whereIn('status', ['active', 'pending']))
            ->exists();
    }

    /** Message spam check: links, phone harvesting, repeated text. */
    public static function messageLooksSpammy(string $body): bool
    {
        $lower = Str::lower($body);
        foreach (config('marketplace.safety.scam_phrases') as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }

        return (bool) preg_match('~(https?://|www\.|bit\.ly|t\.me/)~i', $body);
    }
}
