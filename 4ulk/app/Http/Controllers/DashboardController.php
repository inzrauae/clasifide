<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\ListingStat;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class DashboardController extends Controller
{
    /** Seller dashboard: views, contacts, demand and ad performance. */
    public function index(Request $request)
    {
        $user = $request->user();
        $days = in_array((int) $request->query('days'), [7, 30, 90], true) ? (int) $request->query('days') : 30;
        $ids = $user->listings()->pluck('id');

        $totals = [
            'live' => $user->listings()->live()->count(),
            'views' => (int) $user->listings()->sum('views_count'),
            'phone' => (int) $user->listings()->sum('phone_reveals'),
            'whatsapp' => (int) $user->listings()->sum('whatsapp_clicks'),
            'chats' => Conversation::where('seller_id', $user->id)->count(),
            'saved' => (int) $user->listings()->sum('favorites_count'),
        ];
        $totals['contacts'] = $totals['phone'] + $totals['whatsapp'] + $totals['chats'];
        $totals['rate'] = $totals['views'] ? round($totals['contacts'] / $totals['views'] * 100, 1) : 0;

        // Daily series for the chart
        $from = now()->subDays($days - 1)->startOfDay();
        $rows = ListingStat::whereIn('listing_id', $ids)->where('date', '>=', $from->toDateString())
            ->selectRaw('date, sum(views) as views, sum(phone_reveals + whatsapp_clicks + messages) as contacts')
            ->groupBy('date')->get()->keyBy(fn ($r) => Carbon::parse($r->date)->toDateString());
        $series = collect(range(0, $days - 1))->map(function ($i) use ($from, $rows) {
            $d = $from->copy()->addDays($i)->toDateString();

            return ['date' => $d, 'views' => (int) ($rows[$d]->views ?? 0), 'contacts' => (int) ($rows[$d]->contacts ?? 0)];
        });
        $period = ['views' => $series->sum('views'), 'contacts' => $series->sum('contacts')];

        // Which of my ads get the most interest
        $topAds = $user->listings()->with('images')
            ->orderByRaw('(phone_reveals + whatsapp_clicks + messages_count) DESC')->orderByDesc('views_count')
            ->limit(5)->get();

        // What buyers are looking at most right now (site-wide demand by category)
        $demand = ListingStat::join('listings', 'listings.id', '=', 'listing_stats.listing_id')
            ->where('listing_stats.date', '>=', now()->subDays(7)->toDateString())
            ->selectRaw('listings.category_id, sum(listing_stats.views) as views')
            ->groupBy('listings.category_id')->orderByDesc('views')->limit(6)->get()
            ->map(fn ($r) => ['category' => Category::allCached()->firstWhere('id', $r->category_id), 'views' => (int) $r->views])
            ->filter(fn ($r) => $r['category']);

        return view('dashboard.index', [
            'user' => $user,
            'subscription' => $user->activeSubscription?->load('package'),
            'totals' => $totals,
            'series' => $series,
            'period' => $period,
            'days' => $days,
            'topAds' => $topAds,
            'demand' => $demand,
            'advanced' => $user->hasAnalytics() || $user->isAdmin(),
            'unread' => $user->unreadMessagesCount(),
            'attention' => $user->listings()->whereIn('status', ['pending_payment', 'rejected'])->count(),
        ]);
    }

    public function ads(Request $request)
    {
        $user = $request->user();
        $tab = $request->query('tab', 'active');

        $query = $user->listings()->with(['images', 'category'])->latest()->dashboardTab($tab);

        return view('dashboard.ads', [
            'listings' => $query->paginate(15)->withQueryString(),
            'tab' => $tab,
            'counts' => self::tabCounts($user->listings()),
            'pendingOrders' => self::pendingFeeOrders($user),
            'credits' => $user->activeSubscription?->promotions_left ?? 0,
        ]);
    }

    /** How many ads sit in each "My ads" tab, for the given (already scoped) query. */
    public static function tabCounts($query): array
    {
        return collect(Listing::DASHBOARD_TABS)->map(fn ($_, $tab) => (clone $query)->dashboardTab($tab)->count())->all();
    }

    /** Unpaid premium-fee orders, keyed by the ad they belong to. */
    public static function pendingFeeOrders($user)
    {
        return $user->orders()->where('type', 'posting_fee')->whereIn('status', ['pending', 'awaiting_review'])->get()
            ->keyBy(fn ($o) => $o->meta['listing_id'] ?? 0);
    }

    public function saved(Request $request)
    {
        $listings = $request->user()->favorites()->with(['images', 'category', 'user.activeSubscription'])
            ->orderByPivot('created_at', 'desc')->paginate(20);

        return view('dashboard.saved', compact('listings'));
    }

    /** Buyer inquiry management (lead pipeline). */
    public function leads(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status');

        $leads = Conversation::with(['listing.images', 'buyer', 'latestMessage'])
            ->where('seller_id', $user->id)
            ->when($status && array_key_exists($status, config('marketplace.lead_statuses')), fn ($q) => $q->where('lead_status', $status))
            ->orderByDesc('last_message_at')->paginate(25)->withQueryString();

        $pipeline = Conversation::where('seller_id', $user->id)->selectRaw('lead_status, count(*) as n')
            ->groupBy('lead_status')->pluck('n', 'lead_status');

        return view('dashboard.leads', [
            'leads' => $leads,
            'pipeline' => $pipeline,
            'status' => $status,
            'leadTools' => $user->hasLeadTools() || $user->isAdmin(),
        ]);
    }

    public function billing(Request $request)
    {
        $user = $request->user();

        return view('dashboard.billing', [
            'subscription' => $user->activeSubscription?->load('package'),
            'orders' => $user->orders()->latest()->paginate(20),
        ]);
    }

    public function profile(Request $request)
    {
        return view('dashboard.profile', [
            'user' => $request->user(),
            'districts' => array_keys(config('marketplace.districts')),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $request->merge([
            'phone' => AuthController::normalisePhone((string) $request->input('phone')),
            'whatsapp' => $request->filled('whatsapp') ? AuthController::normalisePhone((string) $request->input('whatsapp')) : null,
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['required', 'regex:/^0\d{9}$/', Rule::unique('users')->ignore($user->id)],
            'whatsapp' => ['nullable', 'regex:/^0\d{9}$/'],
            'district' => ['nullable', Rule::in(array_keys(config('marketplace.districts')))],
            'city' => ['nullable', 'string', 'max:60'],
            'avatar' => ['nullable', 'image', 'max:4096'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        if ($request->hasFile('avatar')) {
            ImageService::delete($user->avatar);
            $user->avatar = ImageService::store($request->file('avatar'), 'avatars', 400)['path'];
        }
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->fill(collect($data)->only(['name', 'email', 'phone', 'whatsapp', 'district', 'city'])->all())->save();

        return back()->with('status', __('Profile saved.'));
    }
}
