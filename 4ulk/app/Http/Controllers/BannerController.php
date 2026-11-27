<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Order;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BannerController extends Controller
{
    public function create(Request $request)
    {
        return view('advertise', [
            'placements' => config('marketplace.banner_placements'),
            'enterprise' => $request->boolean('enterprise'),
            'stats' => cache()->remember('advertise.stats', 600, fn () => [
                'views' => (int) \App\Models\Listing::sum('views_count'),
                'ads' => \App\Models\Listing::live()->count(),
                'members' => \App\Models\User::count(),
            ]),
        ]);
    }

    /** A business books a banner slot. Logged-in users can pay straight away; others are contacted by sales. */
    public function store(Request $request)
    {
        abort_if($request->filled('website_url_confirm'), 422); // honeypot

        $data = $request->validate([
            'advertiser' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'email'],
            'contact_phone' => ['required', 'string', 'max:20'],
            'placement' => ['required', Rule::in(array_keys(config('marketplace.banner_placements')))],
            'months' => ['required', 'integer', 'between:1,'.config('marketplace.banner_max_months', 12)],
            'starts_at' => ['required', 'date', 'after_or_equal:today'],
            'url' => ['nullable', 'url', 'max:255'],
            'image' => ['nullable', 'image', 'max:4096'],
            'message' => ['nullable', 'string', 'max:1500'],
        ]);

        $price = config("marketplace.banner_placements.{$data['placement']}.price") * $data['months'];
        $starts = \Illuminate\Support\Carbon::parse($data['starts_at']);

        $banner = Banner::create([
            'user_id' => $request->user()?->id,
            'advertiser' => $data['advertiser'],
            'contact_email' => $data['contact_email'],
            'contact_phone' => $data['contact_phone'],
            'placement' => $data['placement'],
            'months' => $data['months'],
            'weeks' => $data['months'] * 4,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addMonthsNoOverflow($data['months'])->subDay(),
            'url' => $data['url'] ?? null,
            'alt' => $data['message'] ?? null,
            'image' => $request->hasFile('image') ? ImageService::store($request->file('image'), 'banners', 1600)['path'] : null,
            'price' => $price,
            'status' => 'awaiting_payment',
        ]);

        if ($request->user()) {
            $order = Order::create([
                'user_id' => $request->user()->id,
                'type' => 'banner',
                'description' => "Banner: {$banner->placementLabel()} – {$data['months']} month(s)",
                'amount' => $price,
                'meta' => ['banner_id' => $banner->id],
            ]);

            return redirect()->route('checkout.show', $order);
        }

        return back()->with('status', __('Thanks! Our advertising team will call you within one working day to confirm your booking.'));
    }

    public function click(Banner $banner)
    {
        $banner->increment('clicks');

        return redirect()->away($banner->url ?: route('home'));
    }
}
