<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ListingInteractionController extends Controller
{
    public function revealPhone(Request $request, Listing $listing)
    {
        abort_unless($listing->isLive(), 404);
        $key = 'phone_'.$listing->id;
        if (! $request->session()->has($key) && $request->user()?->id !== $listing->user_id) {
            $listing->track('phone_reveals');
            $request->session()->put($key, true);
        }

        return response()->json(['phone' => $listing->phone, 'tel' => $listing->telHref()]);
    }

    public function whatsapp(Request $request, Listing $listing)
    {
        abort_unless($listing->isLive(), 404);
        $key = 'wa_'.$listing->id;
        if (! $request->session()->has($key) && $request->user()?->id !== $listing->user_id) {
            $listing->track('whatsapp_clicks');
            $request->session()->put($key, true);
        }

        return redirect()->away($listing->whatsappHref());
    }

    public function report(Request $request, Listing $listing)
    {
        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(config('marketplace.report_reasons')))],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $already = Report::where('listing_id', $listing->id)
            ->where(fn ($q) => $q->where('ip', $request->ip())->when($request->user(), fn ($w) => $w->orWhere('user_id', $request->user()->id)))
            ->exists();

        if (! $already) {
            Report::create($data + ['listing_id' => $listing->id, 'user_id' => $request->user()?->id, 'ip' => $request->ip()]);

            // Several independent fraud reports pull the ad for review automatically
            $fraud = Report::where('listing_id', $listing->id)->where('status', 'open')->whereIn('reason', ['fraud', 'wrong_price'])->count();
            if ($fraud >= 3 && $listing->status === 'active') {
                $listing->update(['status' => 'pending', 'is_flagged' => true, 'flag_reason' => 'Reported as fraud by several users']);
            }
        }

        return back()->with('status', __('Thanks. Our team will review this ad within 24 hours.'));
    }

    public function favorite(Request $request, Listing $listing)
    {
        $user = $request->user();
        $result = $user->favorites()->toggle($listing->id);
        $saved = ! empty($result['attached']);
        if ($saved) {
            $listing->increment('favorites_count');
        } elseif ($listing->favorites_count > 0) {
            $listing->decrement('favorites_count');
        }

        if ($request->wantsJson()) {
            return response()->json(['saved' => $saved]);
        }

        return back()->with('status', $saved ? 'Saved to your favourites.' : 'Removed from favourites.');
    }
}
