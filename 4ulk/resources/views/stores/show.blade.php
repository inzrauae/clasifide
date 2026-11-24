@php
    $place = collect([$store->city, $store->district ? __($store->district) : null])->filter()->unique()->implode(', ');
    $wa = $store->whatsapp ? 'https://wa.me/'.preg_replace('/^0/', '94', preg_replace('/\D/', '', $store->whatsapp)) : null;
    $member = (bool) $owner->activeSubscription;
@endphp
<x-layouts.app :title="$store->name" :description="$store->tagline ?? \Illuminate\Support\Str::limit($store->description, 150)" :og-image="$store->banner ? asset($store->banner) : null">

{{-- ============================================================ COVER --}}
<section class="relative h-44 sm:h-72 overflow-hidden {{ $store->banner ? 'bg-night' : 'hero-band' }}">
    @if ($store->banner)
        <img src="{{ asset($store->banner) }}" alt="" class="absolute inset-0 w-full h-full object-cover">
    @else
        {{-- decorative pattern when the business has no cover photo yet --}}
        <div class="absolute inset-0 opacity-[.07]" style="background-image: radial-gradient(#fff 1.2px, transparent 1.2px); background-size: 22px 22px;"></div>
        <div class="absolute -right-24 -top-24 w-96 h-96 rounded-full bg-brand-light/30 blur-2xl"></div>
        <p class="absolute right-4 sm:right-10 bottom-6 sm:bottom-10 font-display font-extrabold text-white/[.06] text-7xl sm:text-[9rem] leading-none select-none whitespace-nowrap" aria-hidden="true">{{ $store->initials() }}</p>
    @endif
    <div class="absolute inset-0 bg-gradient-to-t from-night/70 via-night/10 to-transparent"></div>
</section>

<div class="max-w-6xl mx-auto px-4">

    {{-- ============================================================ HEADER --}}
    <header class="-mt-20 sm:-mt-24 relative card p-5 sm:p-7 shadow-[0_20px_50px_-24px_rgba(30,15,69,.45)]">
        <div class="flex flex-col md:flex-row gap-5 md:items-center">
            <span class="-mt-16 sm:-mt-20 md:mt-0 w-24 h-24 sm:w-32 sm:h-32 rounded-3xl ring-[5px] ring-white brand-gradient text-white text-3xl sm:text-4xl font-display font-extrabold flex items-center justify-center overflow-hidden shrink-0 shadow-xl">
                @if ($store->logo)<img src="{{ asset($store->logo) }}" alt="{{ __(':name logo', ['name' => $store->name]) }}" class="w-full h-full object-cover">@else{{ $store->initials() }}@endif
            </span>

            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($owner->isVerified())<span class="badge bg-emerald-100 text-emerald-700 !text-[11px]">@svg('lucide-shield-check', 'w-3.5 h-3.5') {{ __('VERIFIED BUSINESS') }}</span>@endif
                    @if ($member)<x-member-badge />@endif
                </div>
                <h1 class="font-display mt-1.5 text-[1.75rem] leading-tight sm:text-4xl font-extrabold tracking-tight">{{ $store->name }}</h1>
                @if ($store->tagline)<p class="mt-1 text-muted sm:text-[17px]">{{ $store->tagline }}</p>@endif
                <div class="mt-3 flex flex-wrap gap-2 text-[13px]">
                    @if ($place)<span class="inline-flex items-center gap-1.5 rounded-full bg-mist px-3 py-1 font-semibold text-[#4C1D95]">@svg('lucide-map-pin', 'w-3.5 h-3.5') {{ $place }}</span>@endif
                    @if ($store->opening_hours)<span class="inline-flex items-center gap-1.5 rounded-full bg-mist px-3 py-1 font-semibold text-[#4C1D95]">@svg('lucide-clock', 'w-3.5 h-3.5') {{ $store->opening_hours }}</span>@endif
                </div>
            </div>

            <div class="flex gap-2 md:flex-col lg:flex-row shrink-0">
                @if ($store->phone)<a href="tel:{{ $store->phone }}" class="btn-dark flex-1 !py-3">@svg('lucide-phone', 'w-4 h-4') {{ __('Call') }}</a>@endif
                @if ($wa)<a href="{{ $wa }}" target="_blank" rel="noopener" class="btn bg-wa text-white hover:brightness-105 flex-1 !py-3">@svg('lucide-send', 'w-4 h-4') WhatsApp</a>@endif
                <button type="button" x-data="share(@js($store->name))" @click="go()" class="btn-ghost !py-3 !px-3.5" aria-label="{{ __('Share this store') }}">@svg('lucide-share-2', 'w-4 h-4')</button>
            </div>
        </div>

        {{-- stats --}}
        <dl class="mt-6 grid grid-cols-2 sm:grid-cols-4 rounded-2xl bg-mist/70 divide-x divide-y sm:divide-y-0 divide-line">
            <div class="p-3.5 sm:p-4">
                <dt class="text-[12px] font-semibold text-muted flex items-center gap-1.5">@svg('lucide-package', 'w-3.5 h-3.5') {{ __('Live ads') }}</dt>
                <dd class="font-display text-2xl font-extrabold">{{ number_format($listings->total()) }}</dd>
            </div>
            <div class="p-3.5 sm:p-4">
                <dt class="text-[12px] font-semibold text-muted flex items-center gap-1.5">@svg('lucide-star', 'w-3.5 h-3.5') {{ __('Rating') }}</dt>
                <dd class="flex items-baseline gap-1.5">
                    @if ($rating)
                        <span class="font-display text-2xl font-extrabold">{{ number_format($rating, 1) }}</span>
                        <span class="flex text-amber-400">@for ($i = 1; $i <= 5; $i++)@svg('lucide-star', 'w-3.5 h-3.5 '.($i <= round($rating) ? 'fill-current' : 'text-line'))@endfor</span>
                    @else
                        <span class="font-display text-2xl font-extrabold text-faint">{{ __('New') }}</span>
                    @endif
                </dd>
                <dd><a href="{{ route('sellers.show', [$owner, 'reviews' => 1]) }}" class="text-[12px] text-brand-dark font-semibold hover:underline">{{ __(':count reviews', ['count' => $reviewsCount]) }}</a></dd>
            </div>
            <div class="p-3.5 sm:p-4">
                <dt class="text-[12px] font-semibold text-muted flex items-center gap-1.5">@svg('lucide-eye', 'w-3.5 h-3.5') {{ __('Store visits') }}</dt>
                <dd class="font-display text-2xl font-extrabold">{{ number_format($store->views_count) }}</dd>
            </div>
            <div class="p-3.5 sm:p-4">
                <dt class="text-[12px] font-semibold text-muted flex items-center gap-1.5">@svg('lucide-calendar-days', 'w-3.5 h-3.5') {{ __('On 4U.lk since') }}</dt>
                <dd class="font-display text-2xl font-extrabold">{{ $owner->created_at->translatedFormat('M Y') }}</dd>
            </div>
        </dl>
    </header>

    <div class="mt-6 grid grid-cols-[minmax(0,1fr)] lg:grid-cols-[1fr_300px] gap-6 items-start">

        {{-- ============================================================ ADS --}}
        <section>
            <div class="flex flex-col sm:flex-row gap-3 sm:items-center justify-between">
                <h2 class="font-display text-2xl font-extrabold tracking-tight">
                    {{ request('q') ? __('Results for ":q"', ['q' => request('q')]) : __('Items for sale') }}
                </h2>
                <form method="GET" class="flex items-center gap-1 rounded-full bg-white border border-line pl-4 pr-1 py-1 sm:w-72 focus-within:border-brand focus-within:ring-2 focus-within:ring-brand/20">
                    @if (request('cat'))<input type="hidden" name="cat" value="{{ request('cat') }}">@endif
                    @svg('lucide-search', 'w-4 h-4 text-faint shrink-0')
                    <input name="q" value="{{ request('q') }}" placeholder="{{ __('Search this store') }}" class="flex-1 min-w-0 px-2 py-1.5 text-sm bg-transparent focus:outline-none">
                    <button class="btn-primary !rounded-full !py-1.5 !px-4 !text-xs">{{ __('Search') }}</button>
                </form>
            </div>

            @if ($categories->count() > 1)
                <nav class="mt-4 flex gap-2 overflow-x-auto no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0" aria-label="{{ __('Store categories') }}">
                    <a href="{{ route('stores.show', $store) }}" @class(['shrink-0 rounded-full px-4 py-2 text-[13px] font-bold border transition', 'bg-ink text-white border-ink' => ! request('cat'), 'bg-white border-line hover:border-brand/40' => request('cat')])>{{ __('All items') }}</a>
                    @foreach ($categories as $c)
                        <a href="{{ route('stores.show', [$store, 'cat' => $c->id]) }}" @class(['shrink-0 inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-[13px] font-bold border transition', 'bg-ink text-white border-ink' => (int) request('cat') === $c->id, 'bg-white border-line hover:border-brand/40' => (int) request('cat') !== $c->id])>@svg('lucide-'.$c->icon, 'w-3.5 h-3.5') {{ $c->name }}</a>
                    @endforeach
                </nav>
            @endif

            <div class="mt-5 grid grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
                @forelse ($listings as $ad)
                    <x-ad-tile :listing="$ad" />
                @empty
                    <div class="col-span-full card p-10 text-center">
                        <span class="mx-auto w-14 h-14 rounded-2xl bg-mist text-brand flex items-center justify-center">@svg('lucide-package', 'w-6 h-6')</span>
                        <p class="mt-3 font-extrabold">{{ request('q') || request('cat') ? __('Nothing matches your search') : __('No items right now') }}</p>
                        <p class="text-sm text-muted mt-1">{{ request('q') || request('cat') ? __('Try another word or view all items.') : __('Check back soon, or contact the store directly.') }}</p>
                        @if (request('q') || request('cat'))<a href="{{ route('stores.show', $store) }}" class="btn-ghost mt-4">{{ __('View all items') }}</a>@endif
                    </div>
                @endforelse
            </div>
            <div class="mt-6">{{ $listings->links() }}</div>
        </section>

        {{-- ============================================================ SIDEBAR --}}
        <aside class="space-y-4 lg:sticky lg:top-24">
            @if ($store->description)
                <div class="card p-5">
                    <h2 class="font-extrabold flex items-center gap-2">@svg('lucide-store', 'w-4 h-4 text-brand') {{ __('About the business') }}</h2>
                    <div x-data="{ more: false }" class="mt-2">
                        <p class="text-sm text-muted leading-relaxed whitespace-pre-line" :class="more ? '' : 'line-clamp-6'">{{ $store->description }}</p>
                        @if (mb_strlen($store->description) > 280)
                            <button type="button" @click="more = !more" class="mt-1 text-sm font-bold text-brand-dark" x-text="more ? @js(__('Show less')) : @js(__('Read more'))"></button>
                        @endif
                    </div>
                </div>
            @endif

            <div class="card p-5">
                <h2 class="font-extrabold">{{ __('Contact & location') }}</h2>
                <ul class="mt-3 space-y-3 text-sm">
                    @if ($store->phone)
                        <li class="flex gap-3"><span class="w-8 h-8 rounded-lg bg-mist text-brand flex items-center justify-center shrink-0">@svg('lucide-phone', 'w-4 h-4')</span><span><span class="block text-[11px] text-muted">{{ __('Phone') }}</span><a href="tel:{{ $store->phone }}" class="font-semibold hover:text-brand-dark">{{ $store->phone }}</a></span></li>
                    @endif
                    @if ($store->address || $place)
                        <li class="flex gap-3"><span class="w-8 h-8 rounded-lg bg-mist text-brand flex items-center justify-center shrink-0">@svg('lucide-map-pin', 'w-4 h-4')</span><span><span class="block text-[11px] text-muted">{{ __('Address') }}</span><span class="font-semibold">{{ $store->address ?: $place }}</span></span></li>
                    @endif
                    @if ($store->opening_hours)
                        <li class="flex gap-3"><span class="w-8 h-8 rounded-lg bg-mist text-brand flex items-center justify-center shrink-0">@svg('lucide-clock', 'w-4 h-4')</span><span><span class="block text-[11px] text-muted">{{ __('Opening hours') }}</span><span class="font-semibold">{{ $store->opening_hours }}</span></span></li>
                    @endif
                    @if ($store->email)
                        <li class="flex gap-3"><span class="w-8 h-8 rounded-lg bg-mist text-brand flex items-center justify-center shrink-0">@svg('lucide-mail', 'w-4 h-4')</span><span class="min-w-0"><span class="block text-[11px] text-muted">{{ __('Email') }}</span><a href="mailto:{{ $store->email }}" class="font-semibold hover:text-brand-dark break-all">{{ $store->email }}</a></span></li>
                    @endif
                    @if ($store->website)
                        <li class="flex gap-3"><span class="w-8 h-8 rounded-lg bg-mist text-brand flex items-center justify-center shrink-0">@svg('lucide-globe', 'w-4 h-4')</span><span class="min-w-0"><span class="block text-[11px] text-muted">{{ __('Website') }}</span><a href="{{ $store->website }}" target="_blank" rel="nofollow noopener" class="font-semibold hover:text-brand-dark break-all">{{ parse_url($store->website, PHP_URL_HOST) }}</a></span></li>
                    @endif
                </ul>
                @if ($store->address || $place)
                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode(($store->address ?: $store->name).', '.$place.', Sri Lanka') }}" target="_blank" rel="noopener" class="btn-ghost w-full mt-4">@svg('lucide-navigation', 'w-4 h-4') {{ __('Get directions') }}</a>
                @endif
            </div>

            <a href="{{ route('sellers.show', $owner) }}" class="card p-4 flex items-center gap-3 hover:border-brand/40 transition">
                <span class="w-10 h-10 rounded-full bg-ink text-white text-sm font-bold flex items-center justify-center shrink-0">{{ $owner->initials() }}</span>
                <span class="flex-1 min-w-0"><span class="block text-[11px] text-muted">{{ __('Run by') }}</span><span class="block font-bold truncate">{{ $owner->name }}</span></span>
                @svg('lucide-arrow-right', 'w-4 h-4 text-faint')
            </a>

            <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4 text-[13px] text-amber-900 flex gap-3">
                @svg('lucide-lightbulb', 'w-5 h-5 shrink-0 text-amber-500')
                <p>{{ __('Meet in a safe public place and check the item before you pay.') }} <a href="{{ route('pages.safety') }}" class="font-bold underline">{{ __('Safety tips') }}</a></p>
            </div>
        </aside>
    </div>

    {{-- Promote stores to other sellers --}}
    @guest
        <a href="{{ route('pricing') }}" class="mt-10 rounded-2xl hero-band text-white p-5 sm:p-6 flex items-center gap-4 hover:brightness-110 transition">
            <span class="w-11 h-11 rounded-xl bg-sun text-ink flex items-center justify-center shrink-0">@svg('lucide-store', 'w-5 h-5')</span>
            <span class="flex-1"><span class="block font-extrabold">{{ __('Run a business? Get a store like this.') }}</span><span class="block text-sm text-white/70">{{ __('Packages from Rs 990 a month.') }}</span></span>
            @svg('lucide-arrow-right', 'w-5 h-5 text-sun shrink-0')
        </a>
    @endguest
</div>
</x-layouts.app>
