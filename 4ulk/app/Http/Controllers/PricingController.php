<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Package;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function index(Request $request)
    {
        return view('pricing', [
            'packages' => Package::where('is_active', true)->orderBy('sort')->get(),
            'current' => $request->user()?->activeSubscription,
            'promotions' => config('marketplace.promotions'),
            'premiumCategories' => Category::allCached()->where('posting_fee', '>', 0),
            'placements' => config('marketplace.banner_placements'),
            'stats' => cache()->remember('pricing.stats', 600, fn () => [
                'ads' => \App\Models\Listing::live()->count(),
                'stores' => \App\Models\Store::where('is_active', true)->count(),
                'views' => (int) \App\Models\Listing::sum('views_count'),
            ]),
            'stores' => \App\Models\Store::where('is_active', true)->with('user')
                ->withCount(['listings' => fn ($q) => $q->live()])->latest()->take(6)->get(),
        ]);
    }

    public function subscribe(Request $request, Package $package)
    {
        abort_unless($package->is_active, 404);

        if ($package->is_custom) {
            return redirect()->route('advertise', ['enterprise' => 1])
                ->with('status', __('Tell us about your business and our team will prepare an Enterprise plan.'));
        }
        if ($package->isFree()) {
            return redirect()->route('dashboard')->with('status', __('You are on the Free plan.'));
        }

        $order = Order::create([
            'user_id' => $request->user()->id,
            'type' => 'subscription',
            'description' => "{$package->name} package – {$package->duration_days} days",
            'amount' => $package->price,
            'meta' => ['package_id' => $package->id],
        ]);

        return redirect()->route('checkout.show', $order);
    }
}
