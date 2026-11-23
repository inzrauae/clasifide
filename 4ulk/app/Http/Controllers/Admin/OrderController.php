<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'awaiting_review');
        $orders = Order::with('user')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->filled('q'), fn ($q) => $q->where('reference', 'like', '%'.$request->q.'%'))
            ->latest()->paginate(30)->withQueryString();

        return view('admin.orders', compact('orders', 'status'));
    }

    public function approve(Order $order)
    {
        OrderService::markPaid($order, $order->method ?: 'bank');

        return back()->with('status', "Order {$order->reference} marked as paid and fulfilled.");
    }

    public function reject(Order $order)
    {
        abort_if($order->isPaid(), 422);
        $order->update(['status' => 'failed']);

        return back()->with('status', "Order {$order->reference} rejected.");
    }
}
