<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ImageService;
use App\Services\OrderService;
use App\Services\PayHere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function show(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        return view('checkout.show', [
            'order' => $order,
            'payhere' => PayHere::enabled() && ! $order->isPaid() ? ['url' => PayHere::checkoutUrl(), 'fields' => PayHere::fields($order)] : null,
            'bank' => config('marketplace.bank'),
        ]);
    }

    /** Customer uploads a bank transfer / deposit slip; an admin approves it. */
    public function bank(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);
        abort_if($order->isPaid(), 422);

        $request->validate(['slip' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:6144']]);
        $file = $request->file('slip');

        if ($file->getClientOriginalExtension() === 'pdf' || $file->getMimeType() === 'application/pdf') {
            $dir = 'uploads/slips/'.now()->format('Y/m');
            $name = $order->reference.'-'.time().'.pdf';
            $file->move(public_path($dir), $name);
            $path = $dir.'/'.$name;
        } else {
            $path = ImageService::store($file, 'slips', 1600)['path'];
        }

        $order->update(['bank_slip' => $path, 'method' => 'bank', 'status' => 'awaiting_review']);

        return redirect()->route('dashboard.billing')->with('status', 'Slip received. We will confirm your payment within a few working hours.');
    }

    /** PayHere sends the customer back here; the real confirmation arrives via notify(). */
    public function return(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);
        $order->refresh();

        return redirect()->route('dashboard.billing')->with('status', $order->isPaid()
            ? 'Payment received. Thank you!'
            : 'Thanks! We are confirming your payment with PayHere. This page will update in a minute.');
    }

    public function notify(Request $request)
    {
        $p = $request->all();
        if (! PayHere::verify($p)) {
            Log::warning('PayHere notify with bad signature', ['order' => $p['order_id'] ?? null]);

            return response('invalid', 400);
        }

        $order = Order::where('reference', $p['order_id'])->first();
        if (! $order) {
            return response('unknown order', 404);
        }

        $status = (int) $p['status_code']; // 2 success, 0 pending, -1 cancelled, -2 failed, -3 chargeback
        if ($status === 2 && (float) $p['payhere_amount'] >= (float) $order->amount) {
            OrderService::markPaid($order, 'payhere', collect($p)->except('md5sig')->all());
        } elseif (in_array($status, [-1, -2], true) && ! $order->isPaid()) {
            $order->update(['status' => 'failed', 'method' => 'payhere', 'gateway_payload' => collect($p)->except('md5sig')->all()]);
        }

        return response('ok');
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
    }
}
