@php($statusClass = ['paid' => 'bg-emerald-100 text-emerald-700', 'pending' => 'bg-amber-100 text-amber-800', 'awaiting_review' => 'bg-sky-100 text-sky-700', 'failed' => 'bg-red-100 text-red-700', 'cancelled' => 'bg-slate-100 text-slate-600'])
<x-dashboard-shell title="Package & billing" active="billing">
    <h1 class="text-2xl font-extrabold">{{ __('Package & billing') }}</h1>

    <div class="mt-4 card p-5 flex flex-col sm:flex-row gap-4 sm:items-center">
        <span class="w-12 h-12 rounded-2xl brand-gradient text-white flex items-center justify-center shrink-0">@svg('lucide-crown', 'w-6 h-6')</span>
        <div class="flex-1">
            @if ($subscription)
                <p class="text-lg font-extrabold">{{ __(':package package', ['package' => $subscription->package->name]) }}</p>
                <p class="text-sm text-muted">{{ __('Active until :date (:when)', ['date' => $subscription->ends_at->translatedFormat('j M Y'), 'when' => $subscription->ends_at->diffForHumans()]) }}</p>
                <p class="text-sm mt-1">{{ __(':credits promotion credits · :premium premium ads · up to :live live ads', ['credits' => $subscription->promotions_left, 'premium' => $subscription->premium_ads_left, 'live' => $subscription->package->active_ads]) }}</p>
            @else
                <p class="text-lg font-extrabold">{{ __('Free plan') }}</p>
                <p class="text-sm text-muted">{{ __('Up to :count live ads. Upgrade for a store, promotion credits and analytics.', ['count' => config('marketplace.free_active_ads')]) }}</p>
            @endif
        </div>
        <a href="{{ route('pricing') }}" class="btn-primary">{{ $subscription ? __('Renew or change') : __('See packages') }}</a>
    </div>

    <h2 class="mt-6 font-extrabold">{{ __('Payments') }}</h2>
    <div class="mt-2 card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs text-muted uppercase tracking-wider"><tr class="border-b border-line-soft"><th class="p-3">{{ __('Reference') }}</th><th class="p-3">{{ __('Item') }}</th><th class="p-3">{{ __('Amount') }}</th><th class="p-3">{{ __('Status') }}</th><th class="p-3"></th></tr></thead>
            <tbody class="divide-y divide-line-soft">
                @forelse ($orders as $o)
                    <tr>
                        <td class="p-3 font-mono text-xs">{{ $o->reference }}<br><span class="text-faint font-sans">{{ $o->created_at->translatedFormat('j M Y') }}</span></td>
                        <td class="p-3">{{ $o->description }}</td>
                        <td class="p-3 font-bold whitespace-nowrap">Rs {{ number_format($o->amount) }}</td>
                        <td class="p-3"><span class="badge {{ $statusClass[$o->status] ?? '' }}">{{ mb_strtoupper(__(str_replace('_', ' ', $o->status))) }}</span></td>
                        <td class="p-3 text-right">@if (in_array($o->status, ['pending', 'failed']))<a href="{{ route('checkout.show', $o) }}" class="font-bold text-brand-dark hover:underline">{{ __('Pay') }}</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-8 text-center text-muted">{{ __('No payments yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $orders->links() }}</div>
</x-dashboard-shell>
