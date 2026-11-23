<x-layouts.app>
@php($popular = [
    ['Cars in Colombo', ['cat' => 'vehicles', 'loc' => 'Colombo']],
    ['Land in Gampaha', ['cat' => 'property', 'loc' => 'Gampaha']],
    ['iPhone', ['q' => 'iPhone']],
    ['Houses for sale', ['q' => 'house']],
    ['Scooters', ['q' => 'scooter']],
    ['Jobs in Colombo', ['cat' => 'jobs', 'loc' => 'Colombo']],
    ['Apartments for rent', ['q' => 'apartment']],
    ['Three-wheelers', ['q' => 'three-wheeler']],
])

<section class="hero-band text-white relative overflow-hidden">
    <x-lanka-art class="absolute inset-0" />
    <div class="relative max-w-6xl mx-auto px-4 pt-7 pb-40 sm:pt-14 sm:pb-56 lg:pb-64 text-center">
        <p class="inline-flex items-center gap-1.5 rounded-full bg-white/10 ring-1 ring-white/15 px-3 py-1 text-[11px] sm:text-[12.5px] font-bold tracking-[0.14em] text-sun uppercase backdrop-blur-sm">
            @svg('lucide-map-pin', 'w-3.5 h-3.5') Sri Lanka’s smarter marketplace
        </p>
        <h1 class="font-display mt-3 text-[32px] sm:text-[50px] font-extrabold leading-[1.04] tracking-[-0.03em] drop-shadow-[0_4px_24px_rgba(14,8,32,.45)]">
            Everything local. <br class="sm:hidden"><span class="text-[#FDE68A]">Just for you.</span>
        </h1>
        <p class="mt-2.5 sm:mt-3 text-[14.5px] sm:text-[17px] text-white/80 max-w-2xl mx-auto leading-relaxed">
            Buy and sell vehicles, property, phones and more, find a job or a service, from Jaffna to Galle.
        </p>
        <div class="mt-5 sm:mt-8 max-w-3xl mx-auto text-left">
            <x-search-bar big :counts="$counts['districts']" />
        </div>
        {{-- popular searches: swipeable chips on phones, wrapped on larger screens --}}
        <div class="mt-4 -mx-4 px-4 flex sm:flex-wrap sm:justify-center items-center gap-2 overflow-x-auto no-scrollbar text-[13px]">
            <span class="shrink-0 text-white/60 font-semibold">Popular:</span>
            @foreach (array_slice($popular, 0, 6) as [$label, $params])
                <a href="{{ route('listings.index', $params) }}" class="tap shrink-0 rounded-full bg-white/10 ring-1 ring-white/15 px-3.5 py-2 text-white/90 hover:bg-white/20 backdrop-blur-sm">{{ $label }}</a>
            @endforeach
        </div>
    </div>
</section>

<div class="max-w-6xl mx-auto px-4 -mt-8 sm:-mt-12 relative space-y-6 sm:space-y-8">

    {{-- Categories --}}
    <section class="card p-4 sm:p-6 shadow-[0_12px_40px_-24px_rgba(76,29,149,.35)]" aria-labelledby="cat-title">
        <div class="flex items-center justify-between mb-3 sm:mb-4">
            <h2 id="cat-title" class="text-[17px] sm:text-xl font-extrabold">Browse by category</h2>
            <a href="{{ route('listings.index') }}" class="hit text-sm font-semibold text-brand-dark hover:underline">All {{ number_format($counts['total']) }} ads</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-2 gap-y-1">
            @foreach ($categories as $c)
                @php($n = $counts['categories'][$c->id] ?? 0)
                <a href="{{ route('listings.index', ['cat' => $c->slug]) }}" class="tap group flex items-center gap-3 p-2 sm:p-2.5 rounded-xl hover:bg-[#F8F6FD]">
                    <span class="w-11 h-11 sm:w-12 sm:h-12 rounded-full bg-lilac text-brand flex items-center justify-center shrink-0 group-hover:bg-brand group-hover:text-white transition-colors">
                        @svg('lucide-'.$c->icon, 'w-5 h-5')
                    </span>
                    <span class="min-w-0">
                        <span class="block text-[13px] sm:text-[14.5px] font-semibold leading-tight">
                            <span class="sm:hidden">{{ $c->label() }}</span><span class="hidden sm:inline">{{ $c->name }}</span>
                        </span>
                        <span class="block text-[11.5px] sm:text-xs text-faint mt-0.5">{{ number_format($n) }} {{ \Illuminate\Support\Str::plural('ad', $n) }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    <x-banner-slot :banner="$bannerTop" placement="home_top" />

    {{-- Featured ads (paid placement) --}}
    @if ($featured->isNotEmpty())
        <section aria-labelledby="top-title">
            <div class="flex items-center justify-between mb-3">
                <h2 id="top-title" class="inline-flex items-center gap-2 text-[17px] sm:text-xl font-extrabold">
                    @svg('lucide-trending-up', 'w-5 h-5 text-amber-500') Featured ads
                </h2>
                <a href="{{ route('pricing') }}#promote" class="hit text-sm font-semibold text-brand-dark hover:underline">Feature your ad</a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                @foreach ($featured->take(8) as $ad)<x-ad-tile :listing="$ad" />@endforeach
            </div>
        </section>
    @endif

    {{-- Post ad promo --}}
    <section class="rounded-2xl bg-night text-white p-5 sm:p-7 flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6 overflow-hidden relative">
        <div class="absolute -right-10 -top-10 w-48 h-48 rounded-full bg-brand/40 blur-2xl" aria-hidden="true"></div>
        <div class="relative flex-1">
            <h2 class="text-lg sm:text-2xl font-extrabold">Got something to sell?</h2>
            <p class="mt-1 text-sm text-white/70">Post your ad for free in under two minutes and reach buyers across Sri Lanka.</p>
        </div>
        <a href="{{ route('listings.create') }}" class="tap relative btn-sun px-6 py-3">@svg('lucide-plus', 'w-4 h-4 stroke-[3]') Post your ad for free</a>
    </section>

    {{-- Latest --}}
    <section aria-labelledby="latest-title">
        <div class="flex items-center justify-between mb-3">
            <h2 id="latest-title" class="text-[17px] sm:text-xl font-extrabold">Latest ads</h2>
            <a href="{{ route('listings.index', ['sort' => 'date']) }}" class="hit text-sm font-semibold text-brand-dark hover:underline">See all</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
            @forelse ($latest as $ad)
                <x-ad-tile :listing="$ad" />
            @empty
                <p class="col-span-full text-center text-muted py-10">No ads yet. <a href="{{ route('listings.create') }}" class="text-brand-dark font-bold">Be the first to post!</a></p>
            @endforelse
        </div>
    </section>

    <x-banner-slot :banner="$bannerMiddle" placement="home_middle" />

    {{-- Verified stores --}}
    @if ($stores->isNotEmpty())
        <section aria-labelledby="stores-title">
            <div class="flex items-center justify-between mb-3">
                <h2 id="stores-title" class="inline-flex items-center gap-2 text-[17px] sm:text-xl font-extrabold">@svg('lucide-store', 'w-5 h-5 text-brand') Verified stores</h2>
                <a href="{{ route('pricing') }}" class="hit text-sm font-semibold text-brand-dark hover:underline">Open your store</a>
            </div>
            <div class="flex sm:grid sm:grid-cols-3 lg:grid-cols-6 gap-3 overflow-x-auto no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
                @foreach ($stores as $s)
                    <a href="{{ route('stores.show', $s) }}" class="tap shrink-0 w-36 sm:w-auto card p-3 text-center hover:shadow-md">
                        <span class="mx-auto w-14 h-14 rounded-full brand-gradient text-white font-bold flex items-center justify-center overflow-hidden">
                            @if ($s->logo)<img src="{{ asset($s->logo) }}" alt="" class="w-full h-full object-cover">@else{{ $s->initials() }}@endif
                        </span>
                        <span class="mt-2 block text-[13px] font-bold leading-tight line-clamp-2">{{ $s->name }}</span>
                        <span class="block text-[11.5px] text-faint mt-0.5">{{ $s->listings_count }} ads · {{ $s->district }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Popular + districts --}}
    <section class="grid md:grid-cols-2 gap-4 sm:gap-6">
        <div class="card p-4 sm:p-6">
            <h2 class="text-base sm:text-lg font-extrabold mb-3">Popular searches</h2>
            <div class="flex flex-wrap gap-2">
                @foreach ($popular as [$label, $params])<a href="{{ route('listings.index', $params) }}" class="chip">{{ $label }}</a>@endforeach
            </div>
        </div>
        <div class="card p-4 sm:p-6">
            <h2 class="text-base sm:text-lg font-extrabold mb-3">Ads by district</h2>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                @foreach (array_slice($counts['districts'], 0, 10, true) as $d => $n)
                    <a href="{{ route('listings.index', ['loc' => $d]) }}" class="flex items-center justify-between py-1.5 text-[13.5px] hover:text-brand-dark">{{ $d }} <span class="text-xs text-faint">{{ $n }}</span></a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- For business --}}
    <section class="card p-5 sm:p-7 grid md:grid-cols-[1.2fr_1fr] gap-6 items-center">
        <div>
            <p class="text-xs font-bold tracking-[0.16em] uppercase text-brand">For sellers & businesses</p>
            <h2 class="font-display mt-1 text-2xl sm:text-3xl font-extrabold tracking-tight">More than a place to post ads</h2>
            <p class="mt-2 text-muted text-[15px]">Open your own store page, see who is viewing and contacting you, manage buyer inquiries and promote your best ads to the top.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('pricing') }}" class="btn-primary">See packages from Rs 990/month</a>
                <a href="{{ route('advertise') }}" class="btn-ghost">Banner advertising</a>
            </div>
        </div>
        <ul class="grid grid-cols-2 gap-3 text-sm">
            @foreach ([['lucide-chart-column', 'Seller dashboard', 'Views, contacts & demand'], ['lucide-store', 'Online store', 'Your own branded page'], ['lucide-rocket', 'Promotions', 'Top Ad, Featured, Urgent'], ['lucide-users-round', 'Lead tools', 'Track every buyer inquiry']] as [$icon, $t, $d])
                <li class="rounded-xl bg-mist p-3">
                    @svg($icon, 'w-5 h-5 text-brand')
                    <p class="mt-1.5 font-bold">{{ $t }}</p>
                    <p class="text-[12.5px] text-muted">{{ $d }}</p>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Trust strip --}}
    <section class="grid sm:grid-cols-3 gap-3">
        @foreach ([['lucide-shield-check', 'Verified sellers', 'Shops and agents with a VERIFIED badge are checked by our team.'], ['lucide-message-square', 'Contact directly', 'Chat, call or WhatsApp sellers. No middleman, no fees.'], ['lucide-shield-alert', 'Stay safe', 'Meet in public and never pay in advance.']] as [$icon, $t, $d])
            <div class="flex gap-3 items-start card p-4">
                <span class="w-10 h-10 rounded-xl bg-lilac text-brand flex items-center justify-center shrink-0">@svg($icon, 'w-5 h-5')</span>
                <span><span class="block text-sm font-bold">{{ $t }}</span><span class="block text-[13px] text-muted mt-0.5">{{ $d }}</span></span>
            </div>
        @endforeach
    </section>
</div>
</x-layouts.app>
