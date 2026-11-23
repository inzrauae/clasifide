<x-layouts.app title="Checkout">
<div class="max-w-2xl mx-auto px-4 py-6 sm:py-10">
    <h1 class="text-2xl font-extrabold">Checkout</h1>
    <div class="mt-4 card p-5">
        <p class="text-xs font-bold uppercase tracking-wider text-faint">{{ $order->typeLabel() }} · {{ $order->reference }}</p>
        <p class="mt-1 font-bold">{{ $order->description }}</p>
        <p class="mt-3 text-3xl font-extrabold text-brand-dark">Rs {{ number_format($order->amount) }}</p>
        @if ($order->isPaid())
            <p class="mt-3 rounded-xl bg-emerald-50 text-emerald-700 px-3 py-2 text-sm font-bold">Paid on {{ $order->paid_at->format('j M Y, g:i a') }}. Thank you!</p>
        @elseif ($order->status === 'awaiting_review')
            <p class="mt-3 rounded-xl bg-sky-50 text-sky-700 px-3 py-2 text-sm font-bold">We have your bank slip and are confirming the payment.</p>
        @endif
    </div>

    @unless ($order->isPaid())
        @if ($payhere)
            <form method="POST" action="{{ $payhere['url'] }}" class="mt-4 card p-5">
                @foreach ($payhere['fields'] as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                <h2 class="font-extrabold flex items-center gap-2">@svg('lucide-credit-card', 'w-5 h-5 text-brand') Pay online</h2>
                <p class="text-sm text-muted mt-1">Visa, Mastercard, Amex, eZ Cash, mCash, Genie and more through PayHere. Your package or promotion starts as soon as the payment clears.</p>
                <button class="btn-primary w-full py-3 mt-4">Pay Rs {{ number_format($order->amount) }} securely</button>
            </form>
        @endif

        @if ($bank['enabled'])
            <form method="POST" action="{{ route('checkout.bank', $order) }}" enctype="multipart/form-data" class="mt-4 card p-5">
                @csrf
                <h2 class="font-extrabold flex items-center gap-2">@svg('lucide-landmark', 'w-5 h-5 text-brand') Bank transfer or deposit</h2>
                <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm">
                    <dt class="text-muted">Account name</dt><dd class="font-semibold">{{ $bank['account_name'] }}</dd>
                    <dt class="text-muted">Bank</dt><dd class="font-semibold">{{ $bank['bank_name'] }} – {{ $bank['branch'] }}</dd>
                    <dt class="text-muted">Account no.</dt><dd class="font-semibold font-mono">{{ $bank['account_no'] }}</dd>
                    <dt class="text-muted">Reference</dt><dd class="font-semibold font-mono">{{ $order->reference }}</dd>
                </dl>
                <p class="text-xs text-muted mt-2">Write the reference on the slip, then upload a photo of it below. We confirm slips within a few working hours.</p>
                <input type="file" name="slip" accept="image/*,application/pdf" required class="input mt-3 !py-2 text-sm">
                @error('slip')<p class="error">{{ $message }}</p>@enderror
                <button class="btn-dark w-full py-3 mt-3">Upload slip</button>
            </form>
        @endif
    @endunless

    <p class="mt-6 text-center"><a href="{{ route('dashboard.billing') }}" class="text-sm font-semibold text-muted hover:text-ink">Go to billing</a></p>
</div>
</x-layouts.app>
