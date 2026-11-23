@php
    $max = max(1, $series->max(fn ($p) => $p['views'] + $p['contacts']));
    $tiles = [
        ['Live ads', $totals['live'], 'lucide-tag'],
        ['Total views', $totals['views'], 'lucide-eye'],
        ['Buyer contacts', $totals['contacts'], 'lucide-users-round'],
        ['Contact rate', $totals['rate'].'%', 'lucide-trending-up'],
    ];
@endphp
<x-dashboard-shell title="Seller dashboard" active="dashboard">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold">Hello, {{ \Illuminate\Support\Str::before($user->name, ' ') ?: $user->name }}</h1>
            <p class="text-sm text-muted">Here is how your ads are doing.</p>
        </div>
        <a href="{{ route('listings.create') }}" class="btn-sun">@svg('lucide-plus', 'w-4 h-4 stroke-[3]') Post an ad</a>
    </div>

    @if ($unread || $attention)
        <div class="mt-4 grid sm:grid-cols-2 gap-3">
            @if ($unread)
                <a href="{{ route('messages.index') }}" class="rounded-2xl bg-[#EDE9FE] p-4 flex items-center gap-3 hover:brightness-95">
                    @svg('lucide-inbox', 'w-5 h-5 text-brand') <span class="text-sm font-bold text-[#4C1D95]">{{ $unread }} unread {{ \Illuminate\Support\Str::plural('message', $unread) }} from buyers</span>
                </a>
            @endif
            @if ($attention)
                <a href="{{ route('dashboard.ads', ['tab' => 'pending']) }}" class="rounded-2xl bg-[#FFFBEB] border border-[#FDE68A] p-4 flex items-center gap-3">
                    @svg('lucide-circle-alert', 'w-5 h-5 text-amber-600') <span class="text-sm font-bold text-[#78350F]">{{ $attention }} {{ \Illuminate\Support\Str::plural('ad', $attention) }} need your attention</span>
                </a>
            @endif
        </div>
    @endif

    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ($tiles as [$label, $value, $icon])
            <div class="card p-4">
                <p class="text-xs font-semibold text-muted flex items-center gap-1.5">@svg($icon, 'w-4 h-4 text-brand') {{ $label }}</p>
                <p class="mt-1 text-2xl font-extrabold tabular-nums">{{ is_numeric($value) ? number_format($value) : $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- Contacts breakdown --}}
    <div class="mt-3 card p-4 grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
        @foreach ([['Phone reveals', $totals['phone'], 'lucide-phone'], ['WhatsApp clicks', $totals['whatsapp'], 'lucide-send'], ['Chats', $totals['chats'], 'lucide-message-square'], ['Saved by buyers', $totals['saved'], 'lucide-heart']] as [$l, $v, $i])
            <div><p class="text-lg font-extrabold tabular-nums">{{ number_format($v) }}</p><p class="text-xs text-muted inline-flex items-center gap-1">@svg($i, 'w-3.5 h-3.5') {{ $l }}</p></div>
        @endforeach
    </div>

    {{-- Performance chart --}}
    <div class="mt-4 card p-4 sm:p-5 relative overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-extrabold">Ad performance</h2>
                <p class="text-xs text-muted">{{ number_format($period['views']) }} views · {{ number_format($period['contacts']) }} contacts in the last {{ $days }} days</p>
            </div>
            <div class="flex rounded-xl bg-mist p-1 text-xs font-bold">
                @foreach ([7, 30, 90] as $d)
                    <a href="{{ route('dashboard', ['days' => $d]) }}" class="px-3 py-1.5 rounded-lg {{ $days === $d ? 'bg-white shadow text-brand-dark' : 'text-muted' }}">{{ $d }}d</a>
                @endforeach
            </div>
        </div>
        <div class="{{ $advanced ? '' : 'blur-[3px] pointer-events-none select-none' }}">
            <div class="mt-4 h-44 flex items-end gap-[2px]" role="img" aria-label="Daily views and contacts">
                @foreach ($series as $p)
                    <div class="flex-1 h-full flex flex-col justify-end group relative">
                        <div class="w-full rounded-t-sm bg-[#DDD6FE] group-hover:bg-brand-light" style="height: {{ round($p['views'] / $max * 100, 1) }}%"></div>
                        <div class="w-full bg-sun" style="height: {{ $p['contacts'] ? max(2, round($p['contacts'] / $max * 100, 1)) : 0 }}%"></div>
                        <span class="hidden group-hover:block absolute bottom-full mb-1 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-ink text-white text-[11px] px-2 py-1 z-10">
                            {{ \Illuminate\Support\Carbon::parse($p['date'])->format('j M') }}: {{ $p['views'] }} views, {{ $p['contacts'] }} contacts
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between text-[11px] text-faint">
                <span>{{ \Illuminate\Support\Carbon::parse($series->first()['date'])->format('j M') }}</span>
                <span class="flex gap-3"><span class="inline-flex items-center gap-1"><i class="w-2.5 h-2.5 rounded-sm bg-[#DDD6FE]"></i> Views</span><span class="inline-flex items-center gap-1"><i class="w-2.5 h-2.5 rounded-sm bg-sun"></i> Contacts</span></span>
                <span>Today</span>
            </div>
        </div>
        @unless ($advanced)
            <div class="absolute inset-0 top-14 flex items-center justify-center">
                <a href="{{ route('pricing') }}" class="btn-primary shadow-xl">@svg('lucide-lock', 'w-4 h-4') Unlock daily analytics with Business</a>
            </div>
        @endunless
    </div>

    <div class="mt-4 grid md:grid-cols-2 gap-4">
        {{-- Most in-demand ads --}}
        <div class="card p-4 sm:p-5">
            <h2 class="font-extrabold">Your most wanted ads</h2>
            <p class="text-xs text-muted">Ranked by buyer contacts</p>
            <ul class="mt-3 divide-y divide-line-soft">
                @forelse ($topAds as $ad)
                    <li class="py-2.5 flex items-center gap-3">
                        <div class="relative w-12 h-12 rounded-lg overflow-hidden shrink-0"><x-ad-image :listing="$ad" /></div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ $ad->url() }}" class="block text-sm font-semibold truncate hover:text-brand-dark">{{ $ad->title }}</a>
                            <p class="text-xs text-muted">{{ number_format($ad->views_count) }} views · {{ $ad->phone_reveals + $ad->whatsapp_clicks + $ad->messages_count }} contacts</p>
                        </div>
                        @if ($ad->isLive() && ! $ad->isTop())<a href="{{ route('promote.show', $ad) }}" class="text-xs font-bold text-brand-dark hover:underline shrink-0">Promote</a>@endif
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-muted">No ads yet. <a href="{{ route('listings.create') }}" class="font-bold text-brand-dark">Post one</a></li>
                @endforelse
            </ul>
        </div>

        {{-- Market demand --}}
        <div class="card p-4 sm:p-5">
            <h2 class="font-extrabold">What buyers want this week</h2>
            <p class="text-xs text-muted">Most viewed categories on 4U.lk, last 7 days</p>
            @php($dmax = max(1, $demand->max('views') ?? 1))
            <ul class="mt-3 space-y-2.5">
                @forelse ($demand as $row)
                    <li>
                        <div class="flex justify-between text-sm"><a href="{{ route('listings.index', ['cat' => $row['category']->slug]) }}" class="font-semibold hover:text-brand-dark">{{ $row['category']->name }}</a><span class="text-muted tabular-nums">{{ number_format($row['views']) }}</span></div>
                        <div class="mt-1 h-2 rounded-full bg-mist"><div class="h-2 rounded-full brand-gradient" style="width: {{ round($row['views'] / $dmax * 100) }}%"></div></div>
                    </li>
                @empty
                    <li class="text-sm text-muted">Not enough data yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    @if ($subscription)
        <div class="mt-4 card p-4 flex flex-wrap items-center gap-4 justify-between">
            <div>
                <p class="font-extrabold">{{ $subscription->package->name }} package</p>
                <p class="text-xs text-muted">Renews / ends {{ $subscription->ends_at->format('j M Y') }} · {{ $subscription->promotions_left }} promotion credits · {{ $subscription->premium_ads_left }} premium ads left</p>
            </div>
            <a href="{{ route('dashboard.billing') }}" class="btn-ghost !py-2">Manage</a>
        </div>
    @endif
</x-dashboard-shell>
