<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Services\PromotionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ListingController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $listings = self::queue($request, $status)->paginate(30)->withQueryString();

        return view('admin.listings', compact('listings', 'status'));
    }

    /** Moderation tabs shared by the main queue and each category's queue. */
    public const TABS = ['pending' => 'To review', 'flagged' => 'Flagged', 'active' => 'Live', 'pending_payment' => 'Awaiting fee', 'rejected' => 'Rejected', 'sold' => 'Sold', 'expired' => 'Expired', 'deleted' => 'Deleted', 'all' => 'All'];

    /** The moderation queue for one status tab, with the search box applied. */
    public static function queue(Request $request, string $status): Builder
    {
        return Listing::withTrashed()->with(['user', 'category', 'images'])
            ->withCount(['reports' => fn ($q) => $q->where('status', 'open')])
            ->when($status === 'flagged', fn ($q) => $q->where('is_flagged', true)->whereNull('deleted_at'))
            ->when($status === 'deleted', fn ($q) => $q->onlyTrashed())
            ->when(! in_array($status, ['flagged', 'deleted', 'all'], true), fn ($q) => $q->where('status', $status)->whereNull('deleted_at'))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('title', 'like', '%'.$request->q.'%')->orWhere('phone', 'like', '%'.$request->q.'%')->orWhere('id', $request->q)))
            ->orderByDesc('risk_score')->latest();
    }

    public function approve(Listing $listing)
    {
        $listing->update([
            'status' => 'active',
            'is_flagged' => false,
            'reject_reason' => null,
            'published_at' => $listing->published_at ?? now(),
            'expires_at' => $listing->expires_at && $listing->expires_at->isFuture() ? $listing->expires_at : now()->addDays(config('marketplace.ad_lifetime_days')),
        ]);

        return back()->with('status', "Ad #{$listing->id} approved.");
    }

    public function reject(Request $request, Listing $listing)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $listing->update(['status' => 'rejected', 'reject_reason' => $data['reason']]);

        // Rejected for fraud: knock the seller's trust score down
        if (str_contains(strtolower($data['reason']), 'fraud') || str_contains(strtolower($data['reason']), 'scam')) {
            $listing->user->forceFill(['trust_score' => max(0, $listing->user->trust_score - 25)])->save();
        }

        return back()->with('status', "Ad #{$listing->id} rejected.");
    }

    public function promote(Request $request, Listing $listing)
    {
        $data = $request->validate(['type' => ['required', Rule::in(array_keys(config('marketplace.promotions')))]]);
        PromotionService::apply($listing, $data['type'], 'admin');

        return back()->with('status', "Promotion added to ad #{$listing->id}.");
    }

    public function destroy(Listing $listing)
    {
        $listing->delete();

        return back()->with('status', "Ad #{$listing->id} removed.");
    }
}
