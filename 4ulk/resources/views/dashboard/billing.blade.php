@php($statusClass = ['paid' => 'bg-emerald-100 text-emerald-700', 'pending' => 'bg-amber-100 text-amber-800', 'awaiting_review' => 'bg-sky-100 text-sky-700', 'failed' => 'bg-red-100 text-red-700', 'cancelled' => 'bg-slate-100 text-slate-600'])
<x-dashboard-shell title="Package & billing" active="billing">
    <h1 class="text-2xl font-extrabold">Package & billing</h1>

    <div class="mt-4 card p-5 flex flex-col sm:flex-row gap-4 sm:items-center">
        <span class="w-12 h-12 rounded-2xl brand-gradient text-white flex items-center justify-center shrink-0">@svg('lucide-crown', 'w-6 h-6')</span>
        <div class="flex-1">
            @if ($subscription)
                <p class="text-lg font-extrabold">{{ $subscription->package->name }} package</p>
                <p class="text-sm text-muted">Active until {{ $subscription->ends_at->format('j M Y') }} ({{ $subscription->ends_at->diffForHumans() }})</p>
                <p class="text-sm mt-1"><b>{{ $subscription->promotions_left }}</b> promotion credits · <b>{{ $subscription->premium_ads_left }}</b> premium ads · up to <b>{{ $subscription->package->active_ads }}</b> live ads</p>
            @else
                <p class="text-lg font-extrabold">Free plan</p>
                <p class="text-sm text-muted">Up to {{ config('marketplace.free_active_ads') }} live ads. Upgrade for a store, promotion credits and analytics.</p>
            @endif
        </div>
        <a href="{{ route('pricing') }}" class="btn-primary">{{ $subscription ? 'Renew or change' : 'See packages' }}</a>
    </div>

    <h2 class="mt-6 font-extrabold">Payments</h2>
    <div class="mt-2 card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs text-muted uppercase tracking-wider"><tr class="border-b border-line-soft"><th class="p-3">Reference</th><th class="p-3">Item</th><th class="p-3">Amount</th><th class="p-3">Status</th><th class="p-3"></th></tr></thead>
            <tbody class="divide-y divide-line-soft">
                @forelse ($orders as $o)
                    <tr>
                        <td class="p-3 font-mono text-xs">{{ $o->reference }}<br><span class="text-faint font-sans">{{ $o->created_at->format('j M Y') }}</span></td>
                        <td class="p-3">{{ $o->description }}</td>
                        <td class="p-3 font-bold whitespace-nowrap">Rs {{ number_format($o->amount) }}</td>
                        <td class="p-3"><span class="badge {{ $statusClass[$o->status] ?? '' }}">{{ strtoupper(str_replace('_', ' ', $o->status)) }}</span></td>
                        <td class="p-3 text-right">@if (in_array($o->status, ['pending', 'failed']))<a href="{{ route('checkout.show', $o) }}" class="font-bold text-brand-dark hover:underline">Pay</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-8 text-center text-muted">No payments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $orders->links() }}</div>
</x-dashboard-shell>
