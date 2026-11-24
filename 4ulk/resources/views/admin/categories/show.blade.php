@php
    $rate = $activity['views'] ? round($activity['contacts'] / $activity['views'] * 100, 1) : 0;
    $dmax = max(1, max($districts ?: [1]));
@endphp
<x-admin-shell :title="$category->name" active="categories">
    <x-admin.category-header :category="$category" tab="show" />

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ([
            ['Live ads', number_format($stats['live']), route('admin.categories.ads', [$category, 'status' => 'active']), null],
            ['Sellers with live ads', number_format($stats['sellers']), null, null],
            ['New this week', number_format($stats['new_week']), route('admin.categories.ads', [$category, 'status' => 'all']), null],
            ['Views · 30 days', number_format($activity['views']), null, $rate.'% became contacts'],
        ] as [$l, $v, $href, $sub])
            @if ($href)
                <a href="{{ $href }}" class="card p-4 hover:shadow-md"><p class="text-xs font-semibold text-muted">{{ $l }}</p><p class="mt-1 text-xl font-extrabold">{{ $v }}</p></a>
            @else
                <div class="card p-4"><p class="text-xs font-semibold text-muted">{{ $l }}</p><p class="mt-1 text-xl font-extrabold">{{ $v }}</p>@if ($sub)<p class="text-xs text-muted">{{ $sub }}</p>@endif</div>
            @endif
        @endforeach
    </div>

    <h2 class="mt-6 font-extrabold">Needs action</h2>
    <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ([
            ['To review', $stats['pending'], route('admin.categories.ads', [$category, 'status' => 'pending']), 'lucide-clock'],
            ['Flagged by safety checks', $stats['flagged'], route('admin.categories.ads', [$category, 'status' => 'flagged']), 'lucide-shield-alert'],
            ['Awaiting posting fee', $stats['awaiting_fee'], route('admin.categories.ads', [$category, 'status' => 'pending_payment']), 'lucide-banknote'],
            ['Open reports', $stats['reports'], route('admin.reports'), 'lucide-flag'],
        ] as [$l, $v, $href, $icon])
            <a href="{{ $href }}" class="card p-4 hover:shadow-md {{ $v ? 'ring-2 ring-amber-300' : '' }}">
                <p class="text-xs font-semibold text-muted flex items-center gap-1.5">@svg($icon, 'w-4 h-4 text-brand') {{ $l }}</p>
                <p class="mt-1 text-2xl font-extrabold">{{ $v }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid md:grid-cols-2 gap-4">
        <div class="card p-4">
            <h2 class="font-extrabold">Prices of live ads</h2>
            @if ($prices['count'])
                <dl class="mt-3 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl bg-mist py-3"><dd class="font-extrabold tabular-nums">Rs {{ number_format($prices['low']) }}</dd><dt class="text-[11px] text-muted font-semibold">Lowest</dt></div>
                    <div class="rounded-xl bg-[#EDE9FE] py-3"><dd class="font-extrabold tabular-nums text-brand-dark">Rs {{ number_format($prices['median']) }}</dd><dt class="text-[11px] text-muted font-semibold">Median</dt></div>
                    <div class="rounded-xl bg-mist py-3"><dd class="font-extrabold tabular-nums">Rs {{ number_format($prices['high']) }}</dd><dt class="text-[11px] text-muted font-semibold">Highest</dt></div>
                </dl>
                <p class="mt-2 text-xs text-muted">From {{ number_format($prices['count']) }} priced ads. The safety check holds ads priced under {{ (int) (config('marketplace.safety.low_price_ratio') * 100) }}% of the median.</p>
            @else
                <p class="mt-3 text-sm text-muted">No priced live ads yet.</p>
            @endif
        </div>

        <div class="card p-4">
            <h2 class="font-extrabold">Premium fee income</h2>
            @if ($category->isPremium() || $revenue['total'])
                <ul class="mt-3 space-y-2 text-sm">
                    <li class="flex justify-between"><span>{{ now()->format('F') }}</span><span class="font-bold tabular-nums">Rs {{ number_format($revenue['month']) }} <span class="text-faint font-normal">({{ $revenue['month_count'] }})</span></span></li>
                    <li class="flex justify-between border-t border-line-soft pt-2 font-extrabold"><span>All time</span><span class="tabular-nums">Rs {{ number_format($revenue['total']) }} <span class="text-faint font-normal">({{ $revenue['total_count'] }})</span></span></li>
                </ul>
                <p class="mt-2 text-xs text-muted">Rs {{ number_format($category->posting_fee) }} per ad after {{ $category->free_ads_per_month }} free {{ \Illuminate\Support\Str::plural('ad', $category->free_ads_per_month) }} per seller each month.</p>
            @else
                <p class="mt-3 text-sm text-muted">This category is free to post in. <a href="{{ route('admin.categories.edit', $category) }}" class="font-bold text-brand-dark hover:underline">Set a posting fee</a> to make it premium.</p>
            @endif
        </div>

        <div class="card p-4">
            <h2 class="font-extrabold">Where the live ads are</h2>
            <ul class="mt-3 space-y-2.5">
                @forelse ($districts as $district => $n)
                    <li>
                        <div class="flex justify-between text-sm"><span class="font-semibold">{{ $district }}</span><span class="text-muted tabular-nums">{{ number_format($n) }}</span></div>
                        <div class="mt-1 h-2 rounded-full bg-mist"><div class="h-2 rounded-full brand-gradient" style="width: {{ round($n / $dmax * 100) }}%"></div></div>
                    </li>
                @empty
                    <li class="text-sm text-muted">No live ads yet.</li>
                @endforelse
            </ul>
        </div>

        <div class="card p-4">
            <h2 class="font-extrabold">Highest-risk ads waiting</h2>
            <ul class="mt-2 divide-y divide-line-soft text-sm">
                @forelse ($riskiest as $ad)
                    <li class="py-2 flex justify-between gap-3">
                        <a href="{{ $ad->url() }}" class="truncate hover:text-brand-dark">{{ $ad->title }}<span class="block text-xs text-muted truncate">{{ $ad->flag_reason ?: 'Manual review' }}</span></a>
                        <span class="badge {{ $ad->risk_score >= 40 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800' }} self-center">RISK {{ $ad->risk_score }}</span>
                    </li>
                @empty
                    <li class="py-4 text-muted">Queue is empty.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-4 card p-4">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="font-extrabold">What the live ads are made of</h2>
            <a href="{{ route('admin.categories.edit', $category) }}" class="text-xs font-bold text-brand-dark hover:underline">Edit fields</a>
        </div>
        @if ($specs)
            <div class="mt-3 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($specs as $field => $values)
                    @php($vmax = max($values))
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-faint">{{ $field }}</p>
                        <ul class="mt-1.5 space-y-1.5">
                            @foreach ($values as $value => $n)
                                <li class="text-sm">
                                    <div class="flex justify-between gap-2">
                                        @if (collect($category->selectFields())->contains('name', $field))
                                            <a href="{{ route('admin.categories.ads', [$category, 'status' => 'active', 'spec' => [$field => $value]]) }}" class="truncate hover:text-brand-dark">{{ $value }}</a>
                                        @else
                                            <span class="truncate">{{ $value }}</span>
                                        @endif
                                        <span class="text-muted tabular-nums">{{ $n }}</span>
                                    </div>
                                    <div class="mt-0.5 h-1.5 rounded-full bg-mist"><div class="h-1.5 rounded-full bg-brand-light" style="width: {{ round($n / $vmax * 100) }}%"></div></div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @else
            <p class="mt-3 text-sm text-muted">Not enough live ads with details filled in yet.</p>
        @endif
    </div>
</x-admin-shell>
