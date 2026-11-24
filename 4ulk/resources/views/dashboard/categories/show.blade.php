@php
    $tabs = \App\Models\Listing::DASHBOARD_TABS + ['all' => 'All'];
    $tiles = [
        ['Live ads', $totals['live'], 'lucide-tag'],
        ['Total views', $totals['views'], 'lucide-eye'],
        ['Buyer contacts', $totals['contacts'], 'lucide-users-round'],
        ['Contact rate', $totals['rate'].'%', 'lucide-trending-up'],
    ];
@endphp
<x-dashboard-shell :title="__('My :category ads', ['category' => $category->name])" active="categories">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <span class="w-11 h-11 rounded-xl brand-gradient text-white flex items-center justify-center shrink-0">@svg('lucide-'.$category->icon, 'w-5 h-5')</span>
            <div class="min-w-0">
                <a href="{{ route('dashboard.categories') }}" class="text-xs font-semibold text-muted hover:text-brand-dark">{{ __('← All categories') }}</a>
                <h1 class="text-2xl font-extrabold leading-tight truncate">{{ $category->name }}</h1>
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if ($credits)<span class="text-xs font-bold text-[#4C1D95] bg-mist rounded-full px-3 py-1.5">@svg('lucide-zap', 'w-3.5 h-3.5 inline') {{ __(':count credits', ['count' => $credits]) }}</span>@endif
            @if ($category->is_active)
                <a href="{{ route('listings.create', ['category' => $category->slug]) }}" class="btn-sun !py-2">@svg('lucide-plus', 'w-4 h-4 stroke-[3]') {{ __('Post :category ad', ['category' => $category->label()]) }}</a>
            @endif
        </div>
    </div>

    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ($tiles as [$label, $value, $icon])
            <div class="card p-4">
                <p class="text-xs font-semibold text-muted flex items-center gap-1.5">@svg($icon, 'w-4 h-4 text-brand') {{ __($label) }}</p>
                <p class="mt-1 text-2xl font-extrabold tabular-nums">{{ is_numeric($value) ? number_format($value) : $value }}</p>
            </div>
        @endforeach
    </div>
    <p class="mt-2 text-xs text-muted">{{ __('Last 30 days: :views views and :contacts contacts on your :category ads · :chats chats in total.', ['views' => number_format($activity['views']), 'contacts' => number_format($activity['contacts']), 'category' => $category->label(), 'chats' => number_format($totals['chats'])]) }}</p>

    <div class="mt-4 grid {{ $fee ? 'md:grid-cols-2' : '' }} gap-4">
        @if ($fee)
            <div class="card p-4 sm:p-5">
                <h2 class="font-extrabold flex items-center gap-1.5">@svg('lucide-banknote', 'w-4 h-4 text-brand') {{ __('Posting fee this month') }}</h2>
                <p class="text-xs text-muted">{{ $fee['free'] ? __(':category ads cost Rs :fee each after your first :free free each month.', ['category' => $category->name, 'fee' => number_format($fee['fee']), 'free' => $fee['free']]) : __(':category ads cost Rs :fee each.', ['category' => $category->name, 'fee' => number_format($fee['fee'])]) }}</p>
                @if ($fee['free'])
                    <div class="mt-3">
                        <div class="flex justify-between text-sm"><span>{{ __('Free ads used') }}</span><span class="font-bold tabular-nums">{{ min($fee['used'], $fee['free']) }} / {{ $fee['free'] }}</span></div>
                        <div class="mt-1 h-2 rounded-full bg-mist"><div class="h-2 rounded-full brand-gradient" style="width: {{ round(min($fee['used'], $fee['free']) / $fee['free'] * 100) }}%"></div></div>
                    </div>
                @endif
                @if ($fee['credits'])<p class="mt-2 text-sm">{{ __('Package premium-ad credits left:') }} <b>{{ $fee['credits'] }}</b></p>@endif
                <p class="mt-3 rounded-xl px-3 py-2 text-sm font-bold {{ $fee['next_costs'] ? 'bg-[#FFFBEB] text-[#78350F]' : 'bg-emerald-50 text-emerald-700' }}">
                    {{ $fee['next_costs'] ? __('Your next :category ad: Rs :fee', ['category' => $category->label(), 'fee' => number_format($fee['next_costs'])]) : __('Your next :category ad: free', ['category' => $category->label()]) }}
                </p>
                @if ($fee['next_costs'])<a href="{{ route('pricing') }}" class="mt-2 inline-block text-xs font-bold text-brand-dark hover:underline">{{ __('Packages include free premium ads →') }}</a>@endif
            </div>
        @endif

        <div class="card p-4 sm:p-5">
            <h2 class="font-extrabold flex items-center gap-1.5">@svg('lucide-chart-pie', 'w-4 h-4 text-brand') {{ __('Market prices') }}</h2>
            <p class="text-xs text-muted">{{ __('Median asking price of live :category ads on :site.', ['category' => $category->label(), 'site' => config('marketplace.name')]) }}</p>
            @if ($market['count'])
                <dl class="mt-3 grid {{ $local ? 'grid-cols-2' : 'grid-cols-1' }} gap-2 text-center">
                    <div class="rounded-xl bg-[#EDE9FE] py-3"><dd class="font-extrabold tabular-nums text-brand-dark">Rs {{ number_format($market['median']) }}</dd><dt class="text-[11px] text-muted font-semibold">{{ __('All Sri Lanka') }} · {{ __(':count ads', ['count' => number_format($market['count'])]) }}</dt></div>
                    @if ($local)
                        <div class="rounded-xl bg-mist py-3"><dd class="font-extrabold tabular-nums">Rs {{ number_format($local['median']) }}</dd><dt class="text-[11px] text-muted font-semibold">{{ __($district) }} · {{ __(':count ads', ['count' => number_format($local['count'])]) }}</dt></div>
                    @endif
                </dl>
                <p class="mt-2 text-xs text-muted">{{ __('Range Rs :low to Rs :high. Prices vary a lot by model and condition, so compare like for like.', ['low' => number_format($market['low']), 'high' => number_format($market['high'])]) }}</p>
            @else
                <p class="mt-3 text-sm text-muted">{{ __('Not enough priced ads yet to show a market price.') }}</p>
            @endif
        </div>
    </div>

    <div class="mt-5 flex gap-1 overflow-x-auto no-scrollbar border-b border-line">
        @foreach ($tabs as $k => $label)
            <a href="{{ route('dashboard.categories.show', [$category, 'tab' => $k]) }}" class="shrink-0 px-4 py-2.5 text-sm font-bold border-b-2 -mb-px {{ $tab === $k ? 'border-brand text-brand-dark' : 'border-transparent text-muted hover:text-ink' }}">
                {{ __($label) }} <span class="ml-1 text-xs font-semibold {{ $tab === $k ? 'text-brand' : 'text-faint' }}">{{ $counts[$k] }}</span>
            </a>
        @endforeach
    </div>

    <div class="mt-4 space-y-3">
        @forelse ($listings as $ad)
            <x-dashboard.ad-card :ad="$ad" :pending-order="$pendingOrders[$ad->id] ?? null" :columns="$columns">
                @if ($ad->price && $market['median'] && $market['count'] >= 5)
                    @php($diff = round(($ad->price - $market['median']) / $market['median'] * 100))
                    <p class="mt-1 text-xs {{ abs($diff) <= 15 ? 'text-emerald-700' : 'text-muted' }}">
                        @if (abs($diff) <= 15) {{ __('Close to the market median') }}
                        @elseif ($diff > 0) {{ __(':percent% above the market median', ['percent' => $diff]) }}
                        @else {{ __(':percent% below the market median', ['percent' => abs($diff)]) }}
                        @endif
                    </p>
                @endif
            </x-dashboard.ad-card>
        @empty
            <div class="card p-10 text-center">
                <p class="font-extrabold text-lg">{{ $tab === 'all' ? __('No :category ads yet', ['category' => $category->label()]) : __('No :category ads in this tab', ['category' => $category->label()]) }}</p>
                <p class="text-sm text-muted mt-1">{{ $category->hint }}</p>
                @if ($category->is_active)<a href="{{ route('listings.create', ['category' => $category->slug]) }}" class="btn-primary mt-4">{{ __('Post a :category ad', ['category' => $category->label()]) }}</a>@endif
            </div>
        @endforelse
    </div>
    <div class="mt-5">{{ $listings->links() }}</div>
</x-dashboard-shell>
