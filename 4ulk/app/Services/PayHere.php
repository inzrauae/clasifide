<?php

namespace App\Services;

use App\Models\Order;

/**
 * PayHere (payhere.lk) checkout for Sri Lankan cards, eZ Cash, mCash, Genie etc.
 * Docs: https://support.payhere.lk/api-&-mobile-sdk/checkout-api
 */
class PayHere
{
    public static function enabled(): bool
    {
        return (bool) config('marketplace.payhere.enabled')
            && config('marketplace.payhere.merchant_id')
            && config('marketplace.payhere.merchant_secret');
    }

    public static function checkoutUrl(): string
    {
        return config('marketplace.payhere.sandbox')
            ? 'https://sandbox.payhere.lk/pay/checkout'
            : 'https://www.payhere.lk/pay/checkout';
    }

    private static function secretHash(): string
    {
        return strtoupper(md5(config('marketplace.payhere.merchant_secret')));
    }

    public static function fields(Order $order): array
    {
        $merchantId = config('marketplace.payhere.merchant_id');
        $amount = number_format($order->amount, 2, '.', '');
        $user = $order->user;
        $names = preg_split('/\s+/', trim($user?->name ?? 'Customer'), 2);

        return [
            'merchant_id' => $merchantId,
            'return_url' => route('checkout.return', $order),
            'cancel_url' => route('checkout.show', $order),
            'notify_url' => route('payhere.notify'),
            'order_id' => $order->reference,
            'items' => $order->description,
            'currency' => $order->currency,
            'amount' => $amount,
            'first_name' => $names[0] ?? 'Customer',
            'last_name' => $names[1] ?? '-',
            'email' => $user?->email ?? config('marketplace.support_email'),
            'phone' => $user?->phone ?? '0000000000',
            'address' => $user?->city ?? 'Sri Lanka',
            'city' => $user?->city ?? ($user?->district ?? 'Colombo'),
            'country' => 'Sri Lanka',
            'hash' => strtoupper(md5($merchantId.$order->reference.$amount.$order->currency.self::secretHash())),
        ];
    }

    /** Verify the md5sig PayHere sends to the notify URL. */
    public static function verify(array $p): bool
    {
        $expected = strtoupper(md5(
            ($p['merchant_id'] ?? '').($p['order_id'] ?? '').($p['payhere_amount'] ?? '')
            .($p['payhere_currency'] ?? '').($p['status_code'] ?? '').self::secretHash()
        ));

        return hash_equals($expected, strtoupper($p['md5sig'] ?? ''))
            && ($p['merchant_id'] ?? null) == config('marketplace.payhere.merchant_id');
    }
}
