@php($tabs = \App\Models\Listing::DASHBOARD_TABS)
<x-dashboard-shell title="{{ __('My ads') }}" active="ads">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-extrabold">{{ __('My ads') }}</h1>
        <div class="flex items-center gap-3">
            @if ($credits)<span class="text-xs font-bold text-[#4C1D95] bg-mist rounded-full px-3 py-1.5">@svg('lucide-zap', 'w-3.5 h-3.5 inline') {{ __(':count credits', ['count' => $credits]) }}</span>@endif
            <a href="{{ route('listings.create') }}" class="btn-sun !py-2">@svg('lucide-plus', 'w-4 h-4 stroke-[3]') {{ __('Post ad') }}</a>
        </div>
    </div>

    <div class="mt-4 flex gap-1 overflow-x-auto no-scrollbar border-b border-line">
        @foreach ($tabs as $k => $label)
            <a href="{{ route('dashboard.ads', ['tab' => $k]) }}" class="shrink-0 px-4 py-2.5 text-sm font-bold border-b-2 -mb-px {{ $tab === $k ? 'border-brand text-brand-dark' : 'border-transparent text-muted hover:text-ink' }}">
                {{ __($label) }} <span class="ml-1 text-xs font-semibold {{ $tab === $k ? 'text-brand' : 'text-faint' }}">{{ $counts[$k] }}</span>
            </a>
        @endforeach
    </div>

    <div class="mt-4 space-y-3">
        @forelse ($listings as $ad)
            <x-dashboard.ad-card :ad="$ad" :pending-order="$pendingOrders[$ad->id] ?? null" />
        @empty
            <div class="card p-10 text-center">
                <p class="font-extrabold text-lg">{{ __('Nothing here yet') }}</p>
                <p class="text-sm text-muted mt-1">{{ __('Ads you post will show up here with their views and contacts.') }}</p>
                <a href="{{ route('listings.create') }}" class="btn-primary mt-4">{{ __('Post an ad') }}</a>
            </div>
        @endforelse
    </div>
    <div class="mt-5">{{ $listings->links() }}</div>
</x-dashboard-shell>
