<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Report;
use App\Models\Subscription;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $paid = Order::where('status', 'paid');

        $revenueByType = (clone $paid)->where('paid_at', '>=', now()->startOfMonth())
            ->selectRaw('type, sum(amount) as total, count(*) as n')->groupBy('type')->get()->keyBy('type');

        return view('admin.dashboard', [
            'stats' => [
                'live' => Listing::live()->count(),
                'pending' => Listing::where('status', 'pending')->count(),
                'flagged' => Listing::where('is_flagged', true)->where('status', 'pending')->count(),
                'reports' => Report::where('status', 'open')->count(),
                'users' => User::count(),
                'users_week' => User::where('created_at', '>=', now()->subWeek())->count(),
                'subscribers' => Subscription::where('status', 'active')->where('ends_at', '>', now())->distinct()->count('user_id'),
                'slips' => Order::where('status', 'awaiting_review')->count(),
                'banners' => Banner::where('status', 'pending')->count(),
                'revenue_month' => (clone $paid)->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
                'revenue_total' => (clone $paid)->sum('amount'),
            ],
            'revenueByType' => $revenueByType,
            'recentOrders' => Order::with('user')->latest()->limit(8)->get(),
            'flagged' => Listing::with('user')->where('status', 'pending')->orderByDesc('risk_score')->limit(6)->get(),
        ]);
    }
}
