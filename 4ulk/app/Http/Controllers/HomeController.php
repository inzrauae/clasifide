<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::allCached();

        $counts = Cache::remember('home.counts', 300, fn () => [
            'categories' => Listing::live()->selectRaw('category_id, count(*) as n')->groupBy('category_id')->pluck('n', 'category_id')->all(),
            'districts' => Listing::live()->selectRaw('district, count(*) as n')->groupBy('district')->orderByDesc('n')->pluck('n', 'district')->all(),
            'total' => Listing::live()->count(),
        ]);

        $with = ['images', 'category', 'user.activeSubscription', 'store'];

        $featured = Listing::live()->with($with)
            ->where('featured_until', '>', now())->inRandomOrder()->limit(8)->get();

        // Fall back to Top ads so the section is never empty at launch
        if ($featured->isEmpty()) {
            $featured = Listing::live()->with($with)->where('top_until', '>', now())->inRandomOrder()->limit(4)->get();
        }

        $latest = Listing::live()->with($with)
            ->orderByDesc(\DB::raw('COALESCE(bumped_at, published_at, created_at)'))->limit(12)->get();

        $stores = Store::where('is_active', true)->withCount(['listings' => fn ($q) => $q->live()])
            ->whereHas('user', fn ($q) => $q->whereNotNull('verified_at'))
            ->orderByDesc('listings_count')->limit(6)->get();

        return view('home', [
            'categories' => $categories,
            'counts' => $counts,
            'featured' => $featured,
            'latest' => $latest,
            'stores' => $stores,
            'bannerTop' => Banner::pick('home_top'),
            'bannerMiddle' => Banner::pick('home_middle'),
        ]);
    }
}
