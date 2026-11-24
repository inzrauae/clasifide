<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Listing;
use App\Support\CategoryInsights;
use Illuminate\Http\Request;

/**
 * The seller's back office for each ad type: their Vehicles, Property, Jobs ...
 * ads with the type's own spec columns, fee status and market prices.
 */
class CategoryDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $mine = $user->listings()->selectRaw('category_id, count(*) as total')
            ->selectRaw("sum(case when status = 'active' and (expires_at is null or expires_at > ?) then 1 else 0 end) as live", [now()])
            ->selectRaw('sum(views_count) as views, sum(phone_reveals + whatsapp_clicks + messages_count) as contacts')
            ->groupBy('category_id')->get()->keyBy('category_id');

        // Categories the seller already uses first, then the rest in menu order
        $categories = Category::allCached()->sortBy(fn ($c) => [isset($mine[$c->id]) ? 0 : 1, $c->sort])->values();

        return view('dashboard.categories.index', [
            'categories' => $categories,
            'mine' => $mine,
            'fees' => $categories->filter->isPremium()->mapWithKeys(fn ($c) => [$c->id => CategoryInsights::feeStatus($c, $user)]),
        ]);
    }

    public function show(Request $request, Category $category)
    {
        abort_unless($category->is_active || $request->user()->listings()->where('category_id', $category->id)->exists(), 404);

        $user = $request->user();
        $tab = $request->query('tab', 'active');
        $tab = array_key_exists($tab, Listing::DASHBOARD_TABS) || $tab === 'all' ? $tab : 'active';
        $mine = $user->listings()->where('category_id', $category->id);

        $totals = [
            'live' => (clone $mine)->live()->count(),
            'views' => (int) (clone $mine)->sum('views_count'),
            'contacts' => (int) (clone $mine)->selectRaw('coalesce(sum(phone_reveals + whatsapp_clicks + messages_count), 0) as n')->value('n'),
            'chats' => Conversation::where('seller_id', $user->id)->whereIn('listing_id', (clone $mine)->select('id'))->count(),
        ];
        $totals['rate'] = $totals['views'] ? round($totals['contacts'] / $totals['views'] * 100, 1) : 0;

        $market = CategoryInsights::prices($category);
        $local = $user->district ? CategoryInsights::prices($category, $user->district) : null;

        return view('dashboard.categories.show', [
            'category' => $category,
            'tab' => $tab,
            'counts' => DashboardController::tabCounts($mine) + ['all' => (clone $mine)->count()],
            'listings' => (clone $mine)->with(['images', 'category'])->latest()->dashboardTab($tab)->paginate(15)->withQueryString(),
            'columns' => array_slice(array_column($category->specFields(), 'name'), 0, 4),
            'totals' => $totals,
            'activity' => CategoryInsights::activity($category, 30, $user->id),
            'fee' => CategoryInsights::feeStatus($category, $user),
            'market' => $market,
            'local' => $local && $local['count'] ? $local : null,
            'district' => $user->district,
            'pendingOrders' => DashboardController::pendingFeeOrders($user),
            'credits' => $user->activeSubscription?->promotions_left ?? 0,
        ]);
    }
}
