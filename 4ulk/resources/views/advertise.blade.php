<x-layouts.app :title="$enterprise ? 'Enterprise plans – 4U.lk' : 'Advertise on 4U.lk – banner ads from Rs '.number_format(collect($placements)->min('price')).' a week'" description="Banner advertising on 4U.lk: home page, search results and ad page placements across Sri Lanka. Simple weekly prices.">
<div x-data="{ p: '{{ old('placement', 'home_top') }}', w: {{ (int) old('weeks', 1) }}, prices: @js(collect($placements)->map->price), names: @js(collect($placements)->map->name) }">

{{-- ============================================================ HERO --}}
<section class="hero-band text-white overflow-hidden">
    <div class="max-w-6xl mx-auto px-4 pt-10 pb-20 sm:pt-14 sm:pb-24 grid grid-cols-[minmax(0,1fr)] lg:grid-cols-[1.2fr_1fr] gap-8 items-center">
        <div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 ring-1 ring-white/15 px-3 py-1 text-xs font-bold text-sun">
                @svg('lucide-megaphone', 'w-3.5 h-3.5') {{ $enterprise ? 'Enterprise' : 'Banner advertising' }}
            </span>
            <h1 class="font-display mt-4 text-[2.3rem] leading-[1.05] sm:text-6xl font-extrabold tracking-tight">
                @if ($enterprise) A plan built for <span class="text-sun">your business</span> @else Put your brand where <span class="text-sun">buyers are looking</span> @endif
            </h1>
            <p class="mt-4 text-white/75 text-[15px] sm:text-lg max-w-xl">
                {{ $enterprise ? 'Multiple stores and branches, bulk posting, custom promotion bundles and a dedicated account manager. Tell us about your business and we will call you.' : 'Show your banner on the busiest pages of 4U.lk, in front of people actively shopping in all 25 districts. Simple weekly prices, no agency needed.' }}
            </p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="#{{ $enterprise ? 'book' : 'spots' }}" class="btn-sun !px-6 !py-3 !text-[15px]">{{ $enterprise ? 'Request a call' : 'See ad spots & prices' }} @svg('lucide-arrow-right', 'w-4 h-4')</a>
            </div>
        </div>
        <dl class="grid grid-cols-3 lg:grid-cols-1 gap-3">
            @foreach ([[number_format($stats['views']), 'ad views so far', 'lucide-eye'], [number_format($stats['ads']), 'live ads right now', 'lucide-package'], [number_format($stats['members']), 'registered members', 'lucide-users-round']] as [$n, $l, $icon])
                <div class="rounded-2xl bg-white/[.07] ring-1 ring-white/10 p-3.5 sm:p-4 lg:flex lg:items-center lg:gap-4">
                    <span class="hidden lg:flex w-11 h-11 rounded-xl bg-sun text-ink items-center justify-center shrink-0">@svg($icon, 'w-5 h-5')</span>
                    <div>
                        <dd class="font-display text-2xl sm:text-3xl font-extrabold">{{ $n }}</dd>
                        <dt class="text-xs text-white/60">{{ $l }}</dt>
                    </div>
                </div>
            @endforeach
        </dl>
    </div>
</section>

<div class="max-w-6xl mx-auto px-4">

    {{-- ============================================================ AD SPOTS --}}
    <section id="spots" class="-mt-12 relative scroll-mt-24">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($placements as $key => $pl)
                <button type="button" @click="p = '{{ $key }}'; document.getElementById('book').scrollIntoView({ behavior: 'smooth' })"
                        class="text-left rounded-2xl bg-white border-2 p-4 flex flex-col transition shadow-[0_14px_34px_-20px_rgba(30,15,69,.45)] hover:-translate-y-1"
                        :class="p === '{{ $key }}' ? 'border-brand ring-4 ring-brand/15' : 'border-transparent'">

                    {{-- mini page preview with the ad spot highlighted --}}
                    <div class="relative aspect-[4/3] rounded-xl bg-page border border-line p-2 overflow-hidden" aria-hidden="true">
                        <div class="h-2.5 rounded bg-night mb-1.5"></div>
                        @switch($key)
                            @case('home_top')
                                <div class="h-7 rounded-md bg-sun ring-2 ring-sun-dark flex items-center justify-center text-[9px] font-extrabold text-ink">YOUR BANNER</div>
                                <div class="mt-1.5 h-5 rounded bg-[#DDD6FE]"></div>
                                <div class="mt-1.5 grid grid-cols-4 gap-1">@for ($i = 0; $i < 8; $i++)<div class="h-6 rounded bg-white border border-line"></div>@endfor</div>
                                @break
                            @case('home_middle')
                                <div class="h-5 rounded bg-[#DDD6FE]"></div>
                                <div class="mt-1.5 grid grid-cols-4 gap-1">@for ($i = 0; $i < 4; $i++)<div class="h-6 rounded bg-white border border-line"></div>@endfor</div>
                                <div class="mt-1.5 h-7 rounded-md bg-sun ring-2 ring-sun-dark flex items-center justify-center text-[9px] font-extrabold text-ink">YOUR BANNER</div>
                                <div class="mt-1.5 grid grid-cols-4 gap-1">@for ($i = 0; $i < 4; $i++)<div class="h-6 rounded bg-white border border-line"></div>@endfor</div>
                                @break
                            @case('search_top')
                                <div class="h-3 rounded-full bg-white border border-line"></div>
                                <div class="mt-1.5 h-6 rounded-md bg-sun ring-2 ring-sun-dark flex items-center justify-center text-[9px] font-extrabold text-ink">YOUR BANNER</div>
                                <div class="mt-1.5 space-y-1">@for ($i = 0; $i < 4; $i++)<div class="h-4 rounded bg-white border border-line flex gap-1 p-0.5"><div class="w-5 rounded-sm bg-[#DDD6FE]"></div><div class="flex-1"></div></div>@endfor</div>
                                @break
                            @default
                                <div class="grid grid-cols-[1.6fr_1fr] gap-1.5 h-[calc(100%-1rem)]">
                                    <div class="space-y-1"><div class="h-12 rounded bg-[#DDD6FE]"></div><div class="h-1.5 rounded bg-line w-4/5"></div><div class="h-1.5 rounded bg-line"></div><div class="h-1.5 rounded bg-line w-3/5"></div></div>
                                    <div class="space-y-1"><div class="h-5 rounded bg-white border border-line"></div><div class="h-14 rounded-md bg-sun ring-2 ring-sun-dark flex items-center justify-center text-center text-[8px] leading-tight font-extrabold text-ink">YOUR<br>BANNER</div></div>
                                </div>
                        @endswitch
                        @if (! empty($pl['tag']))
                            <span class="absolute top-1.5 right-1.5 rounded-full bg-brand text-white px-2 py-0.5 text-[9.5px] font-extrabold tracking-wide shadow">{{ strtoupper($pl['tag']) }}</span>
                        @endif
                    </div>

                    <p class="mt-3 font-extrabold leading-snug">{{ $pl['name'] }}</p>
                    <p class="mt-0.5 text-[13px] text-muted flex-1">{{ $pl['blurb'] ?? '' }}</p>
                    <div class="mt-3 pt-3 border-t border-line-soft">
                        <p class="flex items-baseline gap-1">
                            <span class="text-sm font-bold text-muted">Rs</span>
                            <span class="font-display text-[2rem] leading-none font-extrabold text-ink">{{ number_format($pl['price']) }}</span>
                            <span class="text-sm text-muted">/ week</span>
                        </p>
                        <p class="mt-1 text-xs text-muted">Rs {{ number_format($pl['price'] * 4) }} for 4 weeks · artwork {{ $pl['size'] }}</p>
                    </div>
                    <span class="mt-3 btn w-full" :class="p === '{{ $key }}' ? 'brand-gradient text-white' : 'bg-mist text-brand-dark'">
                        <span x-show="p === '{{ $key }}'" class="inline-flex items-center gap-1.5">@svg('lucide-circle-check', 'w-4 h-4') Selected</span>
                        <span x-show="p !== '{{ $key }}'">Choose this spot</span>
                    </span>
                </button>
            @endforeach
        </div>
    </section>

    {{-- ============================================================ WHAT YOU GET + FORM --}}
    <section class="mt-12 sm:mt-16 grid grid-cols-[minmax(0,1fr)] lg:grid-cols-[1fr_400px] gap-8 items-start">
        <div>
            <h2 class="font-display text-3xl sm:text-4xl font-extrabold tracking-tight">Every booking includes</h2>
            <ul class="mt-6 grid sm:grid-cols-2 gap-3">
                @foreach ([
                    ['lucide-monitor-smartphone', 'Mobile & desktop', 'Your banner shows on phones and computers. Most of our buyers browse on mobile.'],
                    ['lucide-chart-column', 'Views & clicks report', 'See how many times your banner was shown and clicked, any time.'],
                    ['lucide-mouse-pointer-click', 'Link to anywhere', 'Send clicks to your website, Facebook page, WhatsApp or 4U.lk store.'],
                    ['lucide-shield-check', 'Checked before launch', 'Our team checks every banner, so your brand appears next to safe content.'],
                    ['lucide-calendar-days', 'Pick your dates', 'Start on any day and run from 1 to 52 weeks. Great for sales and launches.'],
                    ['lucide-palette', 'Need artwork?', 'No designer? Send your logo and offer and we can help you make a banner.'],
                ] as [$icon, $t, $d])
                    <li class="card p-4 flex gap-3">
                        <span class="w-10 h-10 rounded-xl bg-lilac text-brand flex items-center justify-center shrink-0">@svg($icon, 'w-5 h-5')</span>
                        <span><span class="block font-bold">{{ $t }}</span><span class="block text-[13px] text-muted mt-0.5">{{ $d }}</span></span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6 rounded-2xl bg-night text-white p-5 sm:p-6">
                <p class="font-extrabold">How it works</p>
                <ol class="mt-3 grid sm:grid-cols-3 gap-4 text-sm">
                    @foreach (['Choose a spot and your dates', 'Pay by card or bank transfer', 'We check your artwork and go live'] as $i => $step)
                        <li class="flex gap-3 items-start"><span class="w-7 h-7 rounded-full bg-sun text-ink font-extrabold flex items-center justify-center shrink-0">{{ $i + 1 }}</span><span class="text-white/80 pt-1">{{ $step }}</span></li>
                    @endforeach
                </ol>
                <p class="mt-4 text-xs text-white/50">Banners in the same spot rotate with other advertisers. No misleading, adult or illegal content.</p>
            </div>
        </div>

        <form id="book" method="POST" action="{{ route('advertise.store') }}" enctype="multipart/form-data" class="card p-5 sm:p-6 space-y-3 scroll-mt-24 lg:sticky lg:top-24 shadow-[0_20px_50px_-24px_rgba(30,15,69,.45)]">
            @csrf
            <input type="text" name="website_url_confirm" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
            <h2 class="font-display text-2xl font-extrabold">{{ $enterprise ? 'Request a call' : 'Book your banner' }}</h2>

            <div><label class="label">Ad spot</label>
                <select name="placement" x-model="p" class="input">@foreach ($placements as $key => $pl)<option value="{{ $key }}">{{ $pl['name'] }} – Rs {{ number_format($pl['price']) }}/week</option>@endforeach</select></div>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="label">Start date</label><input type="date" name="starts_at" value="{{ old('starts_at', now()->addDays(2)->toDateString()) }}" min="{{ now()->toDateString() }}" required class="input">@error('starts_at')<p class="error">{{ $message }}</p>@enderror</div>
                <div><label class="label">Weeks</label>
                    <div class="flex items-center rounded-xl border border-line overflow-hidden">
                        <button type="button" @click="w = Math.max(1, (w || 1) - 1)" class="w-10 h-[42px] text-lg font-bold hover:bg-mist" aria-label="Fewer weeks">−</button>
                        <input type="number" name="weeks" x-model.number="w" min="1" max="52" required class="w-full min-w-0 text-center font-bold focus:outline-none">
                        <button type="button" @click="w = Math.min(52, (w || 1) + 1)" class="w-10 h-[42px] text-lg font-bold hover:bg-mist" aria-label="More weeks">+</button>
                    </div>
                </div>
            </div>
            <div><label class="label">Business name</label><input name="advertiser" value="{{ old('advertiser', auth()->user()?->stores()->value('name') ?? auth()->user()?->name) }}" required class="input">@error('advertiser')<p class="error">{{ $message }}</p>@enderror</div>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="label">Email</label><input type="email" name="contact_email" value="{{ old('contact_email', auth()->user()?->email) }}" required class="input">@error('contact_email')<p class="error">{{ $message }}</p>@enderror</div>
                <div><label class="label">Phone</label><input name="contact_phone" value="{{ old('contact_phone', auth()->user()?->phone) }}" required class="input"></div>
            </div>
            <div><label class="label">Link (where clicks go)</label><input type="url" name="url" value="{{ old('url') }}" placeholder="https://" class="input">@error('url')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label">Artwork <span class="font-normal text-muted">(optional now)</span></label><input type="file" name="image" accept="image/*" class="input !py-2 text-sm"></div>
            <div><label class="label">Message</label><textarea name="message" rows="2" class="input !text-sm" placeholder="{{ $enterprise ? 'Number of branches, ads per month, categories…' : 'Anything we should know?' }}">{{ old('message') }}</textarea></div>

            <div class="rounded-xl bg-mist p-4">
                <div class="flex justify-between text-sm text-muted"><span x-text="names[p]"></span><span x-text="'Rs ' + (prices[p] || 0).toLocaleString() + ' × ' + (w || 1) + ' wk'"></span></div>
                <div class="mt-1 flex justify-between items-baseline"><span class="font-bold">Total</span><span class="font-display text-2xl font-extrabold text-brand-dark" x-text="'Rs ' + ((prices[p] || 0) * (w || 1)).toLocaleString()"></span></div>
            </div>
            <button class="btn-primary w-full !py-3 !text-[15px]">{{ auth()->check() ? 'Continue to payment' : 'Send booking request' }} @svg('lucide-arrow-right', 'w-4 h-4')</button>
            @guest<p class="text-xs text-muted text-center"><a href="{{ route('login') }}" class="underline">Sign in</a> to pay online straight away.</p>@endguest
        </form>
    </section>
</div>
</div>
</x-layouts.app>
