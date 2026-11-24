<x-layouts.app title="{{ __('4U.lk for business – stores, promotions & seller packages') }}" description="{{ __('Sell more on 4U.lk: your own online store, promotion credits, seller analytics and lead tools. Packages from Rs 990 a month.') }}">

{{-- ============================================================ HERO --}}
<section class="hero-band text-white overflow-hidden">
    <div class="max-w-6xl mx-auto px-4 pt-10 pb-24 sm:pt-16 sm:pb-32 grid grid-cols-[minmax(0,1fr)] lg:grid-cols-[1.05fr_1fr] gap-10 items-center">
        <div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 ring-1 ring-white/15 px-3 py-1 text-xs font-bold text-sun">
                @svg('lucide-sparkles', 'w-3.5 h-3.5') {{ __('4U.lk for business') }}
            </span>
            <h1 class="font-display mt-4 text-[2.4rem] leading-[1.05] sm:text-6xl font-extrabold tracking-tight">
                {!! __('Turn buyers into :customers, every day.', ['customers' => '<span class="text-sun">'.e(__('customers')).'</span>']) !!}
            </h1>
            <p class="mt-4 text-white/75 text-[15px] sm:text-lg max-w-xl">
                {{ __('Open your own store on Sri Lanka\'s smarter marketplace. Push your best ads to the top, see exactly who is interested, and follow up every lead from one dashboard.') }}
            </p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="#plans" class="btn-sun !px-6 !py-3 !text-[15px]">{{ __('See packages') }} @svg('lucide-arrow-right', 'w-4 h-4')</a>
                <a href="{{ auth()->check() ? route('listings.create') : route('register') }}" class="btn !px-6 !py-3 !text-[15px] bg-white/10 ring-1 ring-white/20 text-white hover:bg-white/15">
                    {{ auth()->check() ? __('Post an ad free') : __('Start free') }}
                </a>
            </div>
            <dl class="mt-9 grid grid-cols-3 gap-4 max-w-md">
                @foreach ([[number_format($stats['ads']), __('live ads')], [number_format($stats['views']), __('ad views')], ['25', __('districts covered')]] as [$n, $l])
                    <div>
                        <dt class="sr-only">{{ $l }}</dt>
                        <dd class="font-display text-2xl sm:text-3xl font-extrabold">{{ $n }}</dd>
                        <dd class="text-xs text-white/60">{{ $l }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- Dashboard preview (illustration) --}}
        <div class="relative hidden sm:block" aria-hidden="true">
            <div class="absolute -inset-6 rounded-[2rem] bg-brand-light/20 blur-3xl"></div>
            <div class="relative rounded-3xl bg-white text-ink p-5 shadow-2xl rotate-[1.5deg]">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl brand-gradient text-white text-sm font-bold flex items-center justify-center">{{ __('NA') }}</span>
                        <div>
                            <p class="text-sm font-extrabold leading-tight">{{ __('Negombo Auto Traders') }}</p>
                            <p class="text-[11px] text-muted">{{ __('Seller dashboard · last 7 days') }}</p>
                        </div>
                    </div>
                    <x-member-badge />
                </div>
                <div class="mt-4 grid grid-cols-3 gap-2.5">
                    @foreach ([[__('Views'), '2,481', '+18%'], [__('Contacts'), '146', '+24%'], [__('New leads'), '23', '+9%']] as [$l, $n, $g])
                        <div class="rounded-xl bg-mist p-3">
                            <p class="text-[11px] font-semibold text-muted">{{ $l }}</p>
                            <p class="font-display text-xl font-extrabold">{{ $n }}</p>
                            <p class="text-[11px] font-bold text-emerald-600">{{ $g }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 flex items-end gap-2 h-28 px-1">
                    @foreach ([38, 52, 45, 66, 58, 84, 100] as $i => $h)
                        <div class="flex-1 h-full flex flex-col justify-end items-center gap-1.5">
                            <div class="w-full rounded-t-md min-h-0 {{ $i === 6 ? 'brand-gradient' : 'bg-[#DDD6FE]' }}" style="height: {{ $h }}%"></div>
                            <span class="text-[10px] text-faint">{{ ['M','T','W','T','F','S','S'][$i] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            {{-- floating cards --}}
            <div class="absolute -left-8 -bottom-10 w-64 rounded-2xl bg-white text-ink p-3 shadow-xl flex gap-3 items-start -rotate-2">
                <span class="w-9 h-9 rounded-full bg-wa text-white flex items-center justify-center shrink-0">@svg('lucide-message-square', 'w-4 h-4')</span>
                <div class="min-w-0">
                    <p class="text-[12px] font-extrabold">{{ __('New inquiry · Toyota Aqua') }}</p>
                    <p class="text-[12px] text-muted truncate">{{ __('"Is it still available? Can I see it today?"') }}</p>
                </div>
            </div>
            <div class="absolute -right-3 -top-4 rounded-xl bg-sun text-ink px-3 py-2 shadow-xl text-xs font-extrabold flex items-center gap-1.5 rotate-3">
                @svg('lucide-arrow-up-to-line', 'w-4 h-4') {{ __('TOP AD') }} · {{ __(':count days', ['count' => 7]) }}
            </div>
        </div>
    </div>
</section>

<div class="max-w-6xl mx-auto px-4">

    {{-- ============================================================ BENEFITS --}}
    <section class="-mt-16 sm:-mt-20 relative grid sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        @foreach ([
            ['lucide-store', 'bg-lilac text-brand', 'Your own online store', 'A branded page with your logo, cover photo, opening hours and every ad in one place. Share one link everywhere.'],
            ['lucide-rocket', 'bg-amber-100 text-amber-700', 'Promotion credits', 'Use monthly credits for Top Ad, Featured on Home, Urgent and Bump Up, and keep your stock in front of buyers.'],
            ['lucide-chart-column', 'bg-sky-100 text-sky-700', 'Seller analytics', 'See views, phone reveals, WhatsApp clicks and chats for each ad, day by day, and what buyers are searching for.'],
            ['lucide-users-round', 'bg-emerald-100 text-emerald-700', 'Lead tools', 'Every buyer chat becomes a lead. Mark it new, contacted, negotiating or won, and add private notes.'],
            ['lucide-badge-check', 'bg-yellow-100 text-yellow-700', 'MEMBER & VERIFIED badges', 'Badges on every ad show buyers you are a real, trusted business, not a one-time seller.'],
            ['lucide-car', 'bg-rose-100 text-rose-700', 'Free vehicle & property ads', 'Packages include premium ad credits, so you skip the per-ad fee in high-value categories.'],
        ] as [$icon, $tone, $t, $d])
            <div class="card p-5 sm:p-6 shadow-[0_10px_30px_-18px_rgba(30,15,69,.35)] hover:-translate-y-0.5 hover:shadow-[0_16px_36px_-18px_rgba(30,15,69,.45)] transition">
                <span class="w-11 h-11 rounded-xl {{ $tone }} flex items-center justify-center">@svg($icon, 'w-5 h-5')</span>
                <h3 class="mt-3 font-extrabold text-[17px]">{{ __($t) }}</h3>
                <p class="mt-1 text-sm text-muted leading-relaxed">{{ __($d) }}</p>
            </div>
        @endforeach
    </section>

    {{-- ============================================================ PLANS --}}
    <section id="plans" class="mt-16 sm:mt-20 scroll-mt-24">
        <div class="text-center max-w-2xl mx-auto">
            <p class="text-xs font-bold tracking-[0.16em] uppercase text-brand">{{ __('Seller packages') }}</p>
            <h2 class="font-display mt-1 text-3xl sm:text-4xl font-extrabold tracking-tight">{{ __('Simple monthly plans') }}</h2>
            <p class="mt-2 text-muted">{{ __('Posting stays free for everyone. Upgrade when you sell often. No contract, renew month by month.') }}</p>
        </div>

        <div class="mt-10 grid sm:grid-cols-2 lg:grid-cols-5 gap-4 lg:gap-3 items-stretch">
            @foreach ($packages as $p)
                @php($isCurrent = $current && $current->package_id === $p->id)
                @php($popular = $p->slug === 'business')
                <div @class([
                    'rounded-2xl p-5 flex flex-col relative',
                    'bg-night text-white shadow-2xl ring-2 ring-brand lg:-my-3 lg:py-8' => $popular,
                    'card' => ! $popular,
                ])>
                    @if ($popular)
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-sun text-ink px-3 py-1 text-[11px] font-extrabold tracking-wide shadow">{{ __('★ MOST POPULAR') }}</span>
                    @endif
                    <h3 class="text-lg font-extrabold">{{ $p->name }}</h3>
                    <p class="text-[12.5px] {{ $popular ? 'text-white/60' : 'text-muted' }}">
                        {{ __(['free' => 'For occasional sellers', 'starter' => 'For small shops', 'business' => 'For growing businesses', 'premium' => 'For dealers & agents', 'enterprise' => 'For large brands'][$p->slug] ?? '') }}
                    </p>
                    <div class="mt-4 min-h-[64px]">
                        @if ($p->is_custom)
                            <p class="font-display text-3xl font-extrabold">{{ __('Custom') }}</p>
                            <p class="text-xs {{ $popular ? 'text-white/60' : 'text-muted' }}">{{ __('Volume pricing') }}</p>
                        @elseif ($p->price === 0)
                            <p class="font-display text-3xl font-extrabold">{{ __('Rs 0') }}</p>
                            <p class="text-xs text-muted">{{ __('Free forever') }}</p>
                        @else
                            <p><span class="text-sm font-bold {{ $popular ? 'text-white/70' : 'text-muted' }}">{{ __('Rs') }}</span> <span class="font-display text-3xl font-extrabold">{{ number_format($p->price) }}</span><span class="text-sm {{ $popular ? 'text-white/60' : 'text-muted' }}">{{ __('/mo') }}</span></p>
                            <p class="text-xs {{ $popular ? 'text-sun' : 'text-brand-dark' }} font-semibold">{{ __('About Rs :amount a day', ['amount' => number_format(round($p->price / 30))]) }}</p>
                        @endif
                    </div>
                    <ul class="mt-4 space-y-2.5 text-[13.5px] flex-1">
                        @foreach ($p->features ?? [] as $f)
                            <li class="flex gap-2">@svg('lucide-check', 'w-4 h-4 shrink-0 mt-0.5 '.($popular ? 'text-sun' : 'text-brand')) <span class="{{ $popular ? 'text-white/85' : '' }}">{{ __($f) }}</span></li>
                        @endforeach
                    </ul>
                    <div class="mt-6">
                        @if ($isCurrent)
                            <form method="POST" action="{{ route('pricing.subscribe', $p) }}">@csrf<button class="btn-ghost w-full">{{ __('Current plan · Renew') }}</button></form>
                        @elseif ($p->price === 0 && ! $p->is_custom)
                            <a href="{{ route('register') }}" class="btn-ghost w-full">{{ __('Start free') }}</a>
                        @elseif ($p->is_custom)
                            <a href="{{ route('advertise', ['enterprise' => 1]) }}" class="btn-ghost w-full">{{ __('Contact sales') }}</a>
                        @else
                            @auth
                                <form method="POST" action="{{ route('pricing.subscribe', $p) }}">@csrf<button class="{{ $popular ? 'btn-sun' : 'btn-dark' }} w-full">{{ __('Choose :package', ['package' => $p->name]) }}</button></form>
                            @else
                                <a href="{{ route('login') }}" class="{{ $popular ? 'btn-sun' : 'btn-dark' }} w-full">{{ __('Choose :package', ['package' => $p->name]) }}</a>
                            @endauth
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <p class="mt-5 text-center text-xs text-muted flex flex-wrap justify-center gap-x-5 gap-y-1">
            <span>@svg('lucide-shield-check', 'w-3.5 h-3.5 inline text-emerald-600') {{ __('Pay by card (PayHere) or bank transfer') }}</span>
            <span>@svg('lucide-calendar-days', 'w-3.5 h-3.5 inline text-brand') {{ __('30 days per package, no auto-renewal') }}</span>
        </p>

        {{-- Feature comparison --}}
        <details class="mt-8 card overflow-hidden group">
            <summary class="cursor-pointer list-none p-4 sm:px-6 flex items-center justify-between font-extrabold">
                {{ __('Compare all features') }}
                @svg('lucide-chevron-down', 'w-5 h-5 text-muted transition group-open:rotate-180')
            </summary>
            <div class="overflow-x-auto border-t border-line-soft">
                <table class="w-full text-sm min-w-[640px]">
                    <thead><tr class="text-left bg-mist/60"><th class="p-3 sm:px-6">{{ __('Feature') }}</th>@foreach ($packages as $p)<th class="p-3 text-center {{ $p->slug === 'business' ? 'text-brand-dark' : '' }}">{{ $p->name }}</th>@endforeach</tr></thead>
                    <tbody class="divide-y divide-line-soft">
                        @foreach ([['Live ads at once', fn ($p) => number_format($p->active_ads)], ['Online stores', fn ($p) => $p->stores ?: false], ['Promotion credits / month', fn ($p) => $p->promotions ?: false], ['Free vehicle & property ads', fn ($p) => $p->premium_ads ?: false], ['Seller analytics', fn ($p) => $p->analytics ? true : 'Basic'], ['Lead tools', fn ($p) => (bool) $p->lead_tools], ['MEMBER badge', fn ($p) => $p->price > 0 || $p->is_custom]] as [$label, $fn])
                            <tr>
                                <td class="p-3 sm:px-6 font-semibold">{{ __($label) }}</td>
                                @foreach ($packages as $p)
                                    @php($v = $fn($p))
                                    <td class="p-3 text-center {{ $p->slug === 'business' ? 'bg-lilac/50' : '' }}">
                                        @if ($v === true)@svg('lucide-circle-check', 'w-5 h-5 inline text-brand')
                                        @elseif ($v === false)<span class="text-faint">–</span>
                                        @else{{ __((string) $v) }}@endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </section>

    {{-- ============================================================ PROMOTIONS --}}
    <section id="promote" class="mt-16 sm:mt-20 scroll-mt-24">
        <div class="grid grid-cols-[minmax(0,1fr)] lg:grid-cols-[1fr_1.4fr] gap-8 items-center">
            <div>
                <p class="text-xs font-bold tracking-[0.16em] uppercase text-brand">{{ __('Pay per ad') }}</p>
                <h2 class="font-display mt-1 text-3xl sm:text-4xl font-extrabold tracking-tight">{{ __('Boost a single ad') }}</h2>
                <p class="mt-2 text-muted">{{ __('No package needed. Pick an ad, choose a boost, and pay only for that ad. Package holders pay with credits.') }}</p>
                <a href="{{ route('dashboard.ads') }}" class="btn-primary mt-5">{{ __('Promote one of my ads') }}</a>
            </div>
            <div class="grid sm:grid-cols-2 gap-3">
                @php($look = [
                    'top' => ['lucide-arrow-up-to-line', 'bg-sun text-ink', __('TOP AD')],
                    'featured' => ['lucide-star', 'brand-gradient text-white', __('FEATURED')],
                    'urgent' => ['lucide-zap', 'bg-red-600 text-white', 'URGENT'],
                    'bump' => ['lucide-refresh-cw', 'bg-emerald-600 text-white', 'BUMPED'],
                ])
                @foreach ($promotions as $key => $p)
                    @php([$icon, $tone, $tag] = $look[$key] ?? ['lucide-rocket', 'bg-ink text-white', mb_strtoupper(__($p['name']))])
                    <div class="card p-4 flex flex-col">
                        <div class="flex items-center justify-between">
                            <span class="w-10 h-10 rounded-xl {{ $tone }} flex items-center justify-center">@svg($icon, 'w-5 h-5')</span>
                            <span class="badge {{ $tone }}">{{ $tag }}</span>
                        </div>
                        <p class="mt-3 font-extrabold">{{ __($p['name']) }}</p>
                        <p class="text-[13px] text-muted mt-0.5 flex-1">{{ __($p['blurb']) }}</p>
                        <p class="mt-3 pt-3 border-t border-line-soft flex items-baseline justify-between">
                            <span class="font-display text-xl font-extrabold">Rs {{ number_format($p['price']) }}</span>
                            <span class="text-xs text-muted">{{ $p['days'] ? __(':count days', ['count' => $p['days']]) : __('one time') }} · {{ __('or :count credits', ['count' => $p['credits']]) }}</span>
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================================================ HOW IT WORKS --}}
    <section class="mt-16 sm:mt-20 rounded-3xl bg-white border border-line p-6 sm:p-10">
        <h2 class="font-display text-center text-3xl font-extrabold tracking-tight">{{ __('Up and running in minutes') }}</h2>
        <ol class="mt-8 grid md:grid-cols-3 gap-6 md:gap-4 relative">
            @foreach ([
                ['lucide-user-plus', 'Create a free account', 'Sign up with your phone number and post your first ads free.'],
                ['lucide-package', 'Pick a package', 'Pay by card or bank transfer. Your credits and badge switch on right after payment.'],
                ['lucide-store', 'Open your store', 'Add your logo, cover and opening hours. Your ads move into your store automatically.'],
            ] as $i => [$icon, $t, $d])
                <li class="text-center px-2">
                    <span class="relative mx-auto w-14 h-14 rounded-2xl brand-gradient text-white flex items-center justify-center shadow-lg shadow-brand/30">
                        @svg($icon, 'w-6 h-6')
                        <span class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-sun text-ink text-xs font-extrabold flex items-center justify-center ring-2 ring-white">{{ $i + 1 }}</span>
                    </span>
                    <p class="mt-4 font-extrabold">{{ __($t) }}</p>
                    <p class="mt-1 text-sm text-muted">{{ __($d) }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- ============================================================ STORES SHOWCASE --}}
    @if ($stores->isNotEmpty())
        <section class="mt-16 sm:mt-20">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-bold tracking-[0.16em] uppercase text-brand">{{ __('Already selling') }}</p>
                    <h2 class="font-display mt-1 text-2xl sm:text-3xl font-extrabold tracking-tight">{{ __('Businesses on 4U.lk') }}</h2>
                </div>
            </div>
            <div class="mt-5 flex sm:grid sm:grid-cols-3 lg:grid-cols-6 gap-3 overflow-x-auto no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0 snap-x">
                @foreach ($stores as $s)
                    <a href="{{ route('stores.show', $s) }}" class="card p-4 text-center hover:border-brand/40 hover:-translate-y-0.5 transition shrink-0 w-40 sm:w-auto snap-start">
                        <span class="mx-auto w-14 h-14 rounded-2xl brand-gradient text-white font-bold flex items-center justify-center overflow-hidden">
                            @if ($s->logo)<img src="{{ asset($s->logo) }}" alt="" class="w-full h-full object-cover">@else{{ $s->initials() }}@endif
                        </span>
                        <p class="mt-2.5 text-[13.5px] font-extrabold leading-tight line-clamp-2">{{ $s->name }}</p>
                        <p class="mt-1 text-[11.5px] text-muted">{{ $s->district ? __($s->district) : __('Sri Lanka') }} · {{ __(':count ads', ['count' => $s->listings_count]) }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ============================================================ VEHICLE / PROPERTY + BANNERS --}}
    <section class="mt-16 sm:mt-20 grid lg:grid-cols-2 gap-4">
        <div class="card p-6 sm:p-7">
            <h2 class="font-display text-2xl font-extrabold tracking-tight">{{ __('Vehicle & property ads') }}</h2>
            <p class="mt-1 text-sm text-muted">{{ __('High-value categories carry a small fee, so buyers see serious sellers only.') }}</p>
            <div class="mt-5 space-y-3">
                @foreach ($premiumCategories as $c)
                    <div class="flex items-center gap-4 rounded-xl bg-mist p-3.5">
                        <span class="w-11 h-11 rounded-xl bg-white text-brand flex items-center justify-center shrink-0">@svg('lucide-'.$c->icon, 'w-5 h-5')</span>
                        <div class="flex-1">
                            <p class="font-extrabold">{{ $c->name }}</p>
                            <p class="text-[13px] text-muted">{{ $c->free_ads_per_month ? __(':count free ad a month, then', ['count' => $c->free_ads_per_month]) : __('Per ad') }}</p>
                        </div>
                        <p class="font-display text-xl font-extrabold text-brand-dark whitespace-nowrap">Rs {{ number_format($c->posting_fee) }}</p>
                    </div>
                @endforeach
                <p class="text-xs text-muted">@svg('lucide-circle-check', 'w-3.5 h-3.5 inline text-brand') {{ __('Free with package credits.') }}</p>
            </div>
        </div>
        <div class="rounded-2xl hero-band text-white p-6 sm:p-7 flex flex-col relative overflow-hidden">
            @svg('lucide-megaphone', 'absolute -right-6 -bottom-6 w-44 h-44 text-white/5 -rotate-12')
            <span class="w-11 h-11 rounded-xl bg-sun text-ink flex items-center justify-center">@svg('lucide-megaphone', 'w-5 h-5')</span>
            <h2 class="font-display mt-4 text-2xl font-extrabold tracking-tight">{{ __('Banner advertising') }}</h2>
            <p class="mt-1 text-white/70 text-sm">{{ __('Put your brand in front of buyers across Sri Lanka on the home page, search results and ad pages.') }}</p>
            <ul class="mt-4 space-y-1.5 text-sm flex-1">
                @foreach (collect($placements)->take(4) as $pl)
                    <li class="flex justify-between gap-3 border-b border-white/10 pb-1.5"><span class="text-white/80">{{ __($pl['name']) }}</span><span class="font-bold whitespace-nowrap">{{ __('Rs :price/wk', ['price' => number_format($pl['price'])]) }}</span></li>
                @endforeach
            </ul>
            <a href="{{ route('advertise') }}" class="btn-sun mt-5 self-start">{{ __('Book a banner') }}</a>
        </div>
    </section>

    {{-- ============================================================ FAQ --}}
    <section class="mt-16 sm:mt-20 grid grid-cols-[minmax(0,1fr)] lg:grid-cols-[1fr_1.6fr] gap-8">
        <div>
            <h2 class="font-display text-3xl font-extrabold tracking-tight">{{ __('Questions') }}</h2>
            <p class="mt-2 text-muted">{{ __('Still unsure which plan fits?') }} <a href="{{ route('advertise', ['enterprise' => 1]) }}" class="text-brand-dark font-bold underline">{{ __('Talk to our team') }}</a>.</p>
        </div>
        <div class="space-y-2.5">
            @foreach ([
                ['Do I need a package to sell?', 'No. Anyone can post up to 5 live ads free. Packages are for people who sell often and want a store, promotions and analytics.'],
                ['How do I pay?', 'By card through PayHere, or by bank transfer with a slip upload. Card payments switch on instantly. Bank transfers are checked by our team, usually within a few hours.'],
                ['What is a promotion credit?', 'Credits come with your package each month. A Top Ad costs 2 credits, Featured 3, and Urgent or Bump Up 1, so you never pay per boost.'],
                ['What happens when my package ends?', 'Your ads stay online. Your store, badge and unused credits pause until you renew. Nothing renews or charges automatically.'],
                ['How do I get the VERIFIED badge?', 'Business accounts can ask our team to check their business documents. Verified sellers get more trust and more replies from buyers.'],
            ] as [$q, $a])
                <details class="card group">
                    <summary class="cursor-pointer list-none p-4 sm:px-5 flex items-center justify-between gap-4 font-bold">
                        {{ __($q) }}
                        <span class="w-7 h-7 rounded-full bg-mist flex items-center justify-center shrink-0 transition group-open:rotate-45">@svg('lucide-plus', 'w-4 h-4 text-brand')</span>
                    </summary>
                    <p class="px-4 sm:px-5 pb-4 -mt-1 text-sm text-muted leading-relaxed">{{ __($a) }}</p>
                </details>
            @endforeach
        </div>
    </section>

    {{-- ============================================================ CTA --}}
    <section class="mt-16 sm:mt-20 mb-4 rounded-3xl brand-gradient text-white p-8 sm:p-12 text-center relative overflow-hidden">
        <div class="absolute -top-20 -left-20 w-64 h-64 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-24 -right-10 w-72 h-72 rounded-full bg-sun/20"></div>
        <div class="relative">
            <h2 class="font-display text-3xl sm:text-4xl font-extrabold tracking-tight">{{ __('Ready to sell more?') }}</h2>
            <p class="mt-2 text-white/80 max-w-lg mx-auto">{{ __('Join the businesses already reaching buyers in all 25 districts. Start free, upgrade any time.') }}</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="#plans" class="btn-sun !px-6 !py-3">{{ __('Choose a package') }}</a>
                <a href="{{ auth()->check() ? route('dashboard.store') : route('register') }}" class="btn !px-6 !py-3 bg-white/15 ring-1 ring-white/30 text-white hover:bg-white/25">{{ auth()->check() ? __('Set up my store') : __('Create free account') }}</a>
            </div>
        </div>
    </section>
</div>
</x-layouts.app>
