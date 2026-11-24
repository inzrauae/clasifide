<x-dashboard-shell title="{{ __('My ads by category') }}" active="categories">
    <h1 class="text-2xl font-extrabold">{{ __('My ads by category') }}</h1>
    <p class="text-sm text-muted">{{ __('Each category has its own page with the details buyers look for, your fee status and local market prices.') }}</p>

    <div class="mt-4 grid sm:grid-cols-2 gap-3">
        @foreach ($categories as $c)
            @php($m = $mine[$c->id] ?? null)
            @php($fee = $fees[$c->id] ?? null)
            <a href="{{ route('dashboard.categories.show', $c) }}" class="card p-4 hover:shadow-md flex flex-col {{ $m ? '' : 'bg-white/60' }}">
                <span class="flex items-start gap-3">
                    <span class="w-10 h-10 rounded-xl {{ $m ? 'brand-gradient text-white' : 'bg-lilac text-brand' }} flex items-center justify-center shrink-0">@svg('lucide-'.$c->icon, 'w-5 h-5')</span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-extrabold truncate">{{ $c->name }}</span>
                        <span class="block text-xs text-muted truncate">{{ $c->hint }}</span>
                    </span>
                    @if ($m && $m->live)<span class="badge bg-emerald-100 text-emerald-700 shrink-0">{{ __(':count LIVE', ['count' => $m->live]) }}</span>@endif
                </span>
                @if ($m)
                    <span class="mt-3 grid grid-cols-3 gap-2 text-center">
                        <span class="rounded-xl bg-mist py-1.5"><b class="block tabular-nums">{{ number_format($m->total) }}</b><span class="text-[11px] text-muted font-semibold">{{ __('Ads') }}</span></span>
                        <span class="rounded-xl bg-mist py-1.5"><b class="block tabular-nums">{{ number_format($m->views) }}</b><span class="text-[11px] text-muted font-semibold">{{ __('Views') }}</span></span>
                        <span class="rounded-xl bg-mist py-1.5"><b class="block tabular-nums">{{ number_format($m->contacts) }}</b><span class="text-[11px] text-muted font-semibold">{{ __('Contacts') }}</span></span>
                    </span>
                @endif
                @if ($fee)
                    <span class="mt-3 text-xs {{ $fee['next_costs'] ? 'text-[#78350F]' : 'text-emerald-700' }} font-semibold">
                        @if ($fee['credits'])
                            {{ __(':count package credits: next ad free', ['count' => $fee['credits']]) }}
                        @elseif ($fee['free_left'])
                            {{ __(':left of :free free ads left this month', ['left' => $fee['free_left'], 'free' => $fee['free']]) }}
                        @else
                            {{ __('Premium: Rs :fee per ad', ['fee' => number_format($fee['fee'])]) }}
                        @endif
                    </span>
                @elseif (! $m)
                    <span class="mt-3 text-xs text-muted">{{ __('Free to post · no ads yet') }}</span>
                @endif
            </a>
        @endforeach
    </div>
</x-dashboard-shell>
