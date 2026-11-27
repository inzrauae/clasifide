@php
    $photos = $listing->images;
    $n = max(1, $photos->count());
    $verified = $seller->isVerified();
    $member = (bool) $seller->activeSubscription;
    $specs = array_filter(array_merge(['Condition' => $listing->condition ? __($listing->condition) : null], $listing->specs ?? []));
    $quick = [__('Is this still available?'), __('What is the final price?'), __('Where can I see it?'), __('Can you deliver?')];
@endphp
<x-layouts.app :title="$listing->title.' – '.$listing->priceLabel()" :description="\Illuminate\Support\Str::limit($listing->description, 155)"
               :og-image="$listing->coverUrl()" :canonical="$listing->url()" :hide-bottom-nav="! $isOwner">
@push('head')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org', '@type' => 'Product', 'name' => $listing->title,
    'description' => \Illuminate\Support\Str::limit($listing->description, 300), 'image' => $photos->map->url()->values(),
    'offers' => array_filter(['@type' => 'Offer', 'priceCurrency' => 'LKR', 'price' => $listing->price, 'availability' => 'https://schema.org/InStock',
        'itemCondition' => $listing->condition === 'Brand New' ? 'https://schema.org/NewCondition' : ($listing->condition ? 'https://schema.org/UsedCondition' : null)]),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

<div class="max-w-6xl mx-auto px-4 pb-28 lg:pb-4 sm:py-6" x-data="{ chat: false, report: false }">

    @if ($isOwner)
        <div class="mt-3 sm:mt-0 mb-4 rounded-2xl bg-night text-white p-4 flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-48">
                <p class="text-xs uppercase tracking-wider text-white/60 font-bold">{{ __('Your ad') }} · {{ $listing->statusLabel() }}</p>
                <p class="text-sm mt-0.5">
                    @svg('lucide-eye', 'w-4 h-4 inline') {{ __(':count views', ['count' => number_format($listing->views_count)]) }} ·
                    @svg('lucide-phone', 'w-4 h-4 inline') {{ __(':count calls', ['count' => $listing->phone_reveals]) }} ·
                    @svg('lucide-send', 'w-4 h-4 inline') {{ $listing->whatsapp_clicks }} WhatsApp ·
                    @svg('lucide-message-square', 'w-4 h-4 inline') {{ __(':count chats', ['count' => $listing->messages_count]) }}
                </p>
                @if ($listing->status === 'rejected' && $listing->reject_reason)<p class="text-sm text-red-300 mt-1">{{ __('Reason: :reason', ['reason' => $listing->reject_reason]) }}</p>@endif
            </div>
            <a href="{{ route('promote.show', $listing) }}" class="btn-sun !py-2">@svg('lucide-rocket', 'w-4 h-4') {{ __('Promote') }}</a>
            <a href="{{ route('listings.edit', $listing) }}" class="btn !py-2 bg-white/10 hover:bg-white/20 text-white">@svg('lucide-pencil', 'w-4 h-4') {{ __('Edit') }}</a>
        </div>
    @endif

    <nav aria-label="{{ __('Breadcrumb') }}" class="hidden sm:flex items-center flex-wrap gap-1 text-[12.5px] text-muted">
        <a href="{{ route('home') }}" class="hover:text-brand-dark hover:underline">{{ __('Home') }}</a> @svg('lucide-chevron-right', 'w-3.5 h-3.5 text-[#C4B5FD]')
        <a href="{{ route('listings.index', ['cat' => $listing->category->slug]) }}" class="hover:text-brand-dark hover:underline">{{ $listing->category->name }}</a> @svg('lucide-chevron-right', 'w-3.5 h-3.5 text-[#C4B5FD]')
        <a href="{{ route('listings.index', ['cat' => $listing->category->slug, 'loc' => $listing->district]) }}" class="hover:text-brand-dark hover:underline">{{ __($listing->district) }}</a> @svg('lucide-chevron-right', 'w-3.5 h-3.5 text-[#C4B5FD]')
        <span class="text-ink font-semibold">{{ \Illuminate\Support\Str::limit($listing->title, 40) }}</span>
    </nav>

    <div class="sm:mt-4 grid grid-cols-[minmax(0,1fr)] lg:grid-cols-[1fr_340px] gap-4 sm:gap-6 items-start">
        <article class="-mx-4 sm:mx-0 bg-white sm:rounded-2xl border-b sm:border border-line overflow-hidden flex flex-col">
            <div class="order-2 sm:order-none p-4 sm:p-6 pb-0 sm:pb-4">
                <h1 class="text-[20px] sm:text-[26px] font-extrabold leading-tight">{{ $listing->title }}</h1>
                <p class="mt-1.5 text-[12.5px] sm:text-[13px] text-muted flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span class="inline-flex items-center gap-1">@svg('lucide-clock', 'w-3.5 h-3.5') {{ __('Posted :date', ['date' => ($listing->published_at ?? $listing->created_at)->translatedFormat('j M, g:i a')]) }}</span>
                    <span class="inline-flex items-center gap-1">@svg('lucide-map-pin', 'w-3.5 h-3.5') {{ $listing->place() }}</span>
                    <span class="inline-flex items-center gap-1">@svg('lucide-eye', 'w-3.5 h-3.5') {{ __(':count views', ['count' => number_format($listing->views_count)]) }}</span>
                </p>
            </div>

            {{-- Gallery: swipe on phones, arrows on larger screens --}}
            <div class="order-1 sm:order-none" x-data="gallery({{ $n }})">
                <div class="relative bg-deep aspect-[4/3] sm:aspect-[16/10]">
                    <div x-ref="strip" @scroll.debounce.50ms="onScroll" class="absolute inset-0 flex overflow-x-auto snap-x snap-mandatory no-scrollbar overscroll-x-contain">
                        @forelse ($photos as $p)
                            <div class="relative w-full h-full shrink-0 snap-center"><x-ad-image :listing="$listing" :src="$p->url()" class="!object-contain" /></div>
                        @empty
                            <div class="relative w-full h-full shrink-0"><x-ad-image :listing="$listing" /></div>
                        @endforelse
                    </div>
                    <div class="absolute top-3 left-3 flex gap-1.5">
                        @if ($listing->isTop())<span class="badge !text-[11px] !px-2 !py-1 bg-sun text-ink">{{ __('TOP AD') }}</span>@endif
                        @if ($listing->isUrgent())<span class="badge !text-[11px] !px-2 !py-1 bg-red-600 text-white">{{ __('URGENT') }}</span>@endif
                    </div>
                    @if ($n > 1)
                        <button @click="go(i - 1)" aria-label="{{ __('Previous photo') }}" class="tap hidden sm:flex absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 items-center justify-center shadow">@svg('lucide-chevron-left', 'w-5 h-5')</button>
                        <button @click="go(i + 1)" aria-label="{{ __('Next photo') }}" class="tap hidden sm:flex absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 items-center justify-center shadow">@svg('lucide-chevron-right', 'w-5 h-5')</button>
                        <span class="absolute bottom-3 right-3 text-xs font-semibold text-white bg-black/55 px-2 py-1 rounded-full"><span x-text="i + 1">1</span> / {{ $n }}</span>
                    @endif
                </div>
                @if ($n > 1)
                    <div class="hidden sm:flex gap-2 p-3 overflow-x-auto no-scrollbar border-b border-line-soft">
                        @foreach ($photos as $k => $p)
                            <button @click="go({{ $k }})" aria-label="{{ __('Photo :n', ['n' => $k + 1]) }}" class="relative w-20 h-16 rounded-lg overflow-hidden shrink-0 ring-2" :class="i === {{ $k }} ? 'ring-brand' : 'ring-transparent opacity-70'">
                                <img src="{{ $p->url() }}" alt="" class="absolute inset-0 w-full h-full object-cover" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="order-3 sm:order-none p-4 sm:p-6 space-y-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[24px] sm:text-[30px] font-extrabold text-brand-dark leading-none">{{ $listing->priceLabel() }}</p>
                        @if ($listing->negotiable)<p class="text-[13px] text-muted mt-1">{{ __('Negotiable') }}</p>@endif
                    </div>
                    <div class="flex gap-2">
                        <x-save-button :listing="$listing" :saved="$saved" class="!w-10 !h-10 !shadow-none border border-line" />
                        <button x-data="share(@js($listing->title))" @click="go()" class="tap w-10 h-10 rounded-full border border-line flex items-center justify-center" aria-label="{{ __('Share') }}">@svg('lucide-share-2', 'w-4 h-4')</button>
                    </div>
                </div>

                @if ($specs)
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 border-t border-line-soft">
                        @foreach ($specs as $k => $v)
                            <div class="flex justify-between gap-4 py-2.5 border-b border-line-soft text-[14px]"><dt class="text-muted">{{ __($k) }}</dt><dd class="font-semibold text-right">{{ $v }}</dd></div>
                        @endforeach
                    </dl>
                @endif

                <div>
                    <h2 class="text-base font-bold mb-2">{{ __('Description') }}</h2>
                    <p class="text-[14.5px] text-[#334155] leading-relaxed whitespace-pre-line">{{ $listing->description }}</p>
                </div>

                @unless ($isOwner)
                    <div class="pt-1">
                        <button x-show="!report" @click="report = true" class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-muted hover:text-red-600">@svg('lucide-flag', 'w-4 h-4') {{ __('Report this ad') }}</button>
                        <form x-show="report" x-cloak method="POST" action="{{ route('listings.report', $listing) }}" class="rounded-xl border border-red-100 bg-red-50/50 p-3 space-y-2" x-data="{ reason: '' }">
                            @csrf
                            <p class="text-[13px] font-bold">{{ __('Why are you reporting this ad?') }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach (config('marketplace.report_reasons') as $k => $label)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="reason" value="{{ $k }}" x-model="reason" class="peer sr-only">
                                        <span class="inline-block text-[12.5px] font-semibold px-3 py-1.5 rounded-full bg-white border border-line peer-checked:border-red-400 peer-checked:text-red-600">{{ __($label) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <textarea name="details" rows="2" maxlength="1000" placeholder="{{ __('Anything else we should know? (optional)') }}" class="input !text-sm"></textarea>
                            <div class="flex gap-2"><button :disabled="!reason" class="btn-danger !py-2">{{ __('Send report') }}</button><button type="button" @click="report = false" class="btn-ghost !py-2">{{ __('Cancel') }}</button></div>
                        </form>
                    </div>
                @endunless
            </div>
        </article>

        {{-- Seller column --}}
        <aside class="space-y-4 lg:sticky lg:top-[88px]">
            <div class="card p-4 sm:p-5">
                <p class="text-[12px] font-semibold text-faint uppercase tracking-wider">{{ $listing->category->slug === 'jobs' ? __('Posted by') : __('For sale by') }}</p>
                <a href="{{ $listing->store ? route('stores.show', $listing->store) : route('sellers.show', $seller) }}" class="mt-2 flex items-center gap-3 group">
                    <span class="w-12 h-12 rounded-full brand-gradient text-white font-bold flex items-center justify-center shrink-0 overflow-hidden">
                        @if ($listing->store?->logo)<img src="{{ asset($listing->store->logo) }}" class="w-full h-full object-cover" alt="">@elseif ($seller->avatar)<img src="{{ asset($seller->avatar) }}" class="w-full h-full object-cover" alt="">@else{{ $listing->store?->initials() ?? $seller->initials() }}@endif
                    </span>
                    <span class="min-w-0">
                        <span class="block font-bold truncate group-hover:text-brand-dark">{{ $listing->sellerName() }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            @if ($verified)<span class="badge bg-emerald-100 text-emerald-700">@svg('lucide-shield-check', 'w-3 h-3') {{ __('VERIFIED') }}</span>@endif
                            @if ($member)<x-member-badge />@endif
                            @if (! $verified && ! $member)<span class="inline-flex items-center gap-1 text-[11px] font-semibold text-muted">@svg('lucide-circle-user', 'w-3.5 h-3.5') {{ __('Individual seller') }}</span>@endif
                        </span>
                    </span>
                </a>
                <div class="mt-3 grid grid-cols-3 text-center text-[12px] border border-line-soft rounded-xl divide-x divide-line-soft">
                    <div class="py-2"><p class="font-extrabold text-[14px]">{{ $rating ? number_format($rating, 1).'★' : '–' }}</p><p class="text-faint">{{ __(':count reviews', ['count' => $reviewsCount]) }}</p></div>
                    <div class="py-2"><p class="font-extrabold text-[14px]">{{ $sellerAds }}</p><p class="text-faint">{{ __('live ads') }}</p></div>
                    <div class="py-2"><p class="font-extrabold text-[14px]">{{ $seller->created_at->format('Y') }}</p><p class="text-faint">{{ __('member since') }}</p></div>
                </div>
                @if ($seller->last_seen_at)<p class="mt-2 text-[12px] text-muted">{{ __('Active :when', ['when' => $seller->last_seen_at->diffForHumans()]) }}</p>@endif

                {{-- Contact options: call, chat, WhatsApp --}}
                <div class="mt-4 rounded-xl border border-line-soft divide-y divide-line-soft overflow-hidden">
                    <button type="button" x-data="phoneReveal('{{ route('listings.phone', $listing) }}', @js($listing->maskedPhone()))" @click="reveal()" class="tap w-full flex items-center gap-3 px-3.5 py-3 text-left hover:bg-mist">
                        <span class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">@svg('lucide-phone', 'w-4 h-4')</span>
                        <span class="min-w-0">
                            <span class="block font-extrabold text-[15px] tracking-wide" x-text="label">{{ $listing->maskedPhone() }}</span>
                            <span class="block text-[12px] text-muted" x-text="tel ? @js(__('Tap to call')) : @js(__('Click to show phone number'))">{{ __('Click to show phone number') }}</span>
                        </span>
                    </button>
                    @if ($ownAd)
                        <div class="w-full flex items-center gap-3 px-3.5 py-3 opacity-60" title="{{ __('This is your own ad.') }}">
                            <span class="w-9 h-9 rounded-full bg-violet-100 text-brand-dark flex items-center justify-center shrink-0">@svg('lucide-message-square', 'w-4 h-4')</span>
                            <span><span class="block font-bold text-[14px]">{{ __('Chat') }}</span><span class="block text-[12px] text-muted">{{ __('Buyers can message you here') }}</span></span>
                        </div>
                    @else
                        <button type="button" @click="{{ auth()->check() ? 'chat = true' : "window.location='".route('login')."'" }}" class="tap w-full flex items-center gap-3 px-3.5 py-3 text-left hover:bg-mist">
                            <span class="w-9 h-9 rounded-full bg-violet-100 text-brand-dark flex items-center justify-center shrink-0">@svg('lucide-message-square', 'w-4 h-4')</span>
                            <span><span class="block font-bold text-[14px]">{{ __('Chat') }}</span><span class="block text-[12px] text-muted">{{ __('Message the seller on 4U.lk') }}</span></span>
                        </button>
                    @endif
                    <a href="{{ route('listings.whatsapp', $listing) }}" target="_blank" rel="noopener" class="tap w-full flex items-center gap-3 px-3.5 py-3 hover:bg-mist">
                        <span class="w-9 h-9 rounded-full bg-wa text-white flex items-center justify-center shrink-0">@svg('lucide-send', 'w-4 h-4')</span>
                        <span><span class="block font-bold text-[14px]">WhatsApp</span><span class="block text-[12px] text-muted">{{ __('Chat on WhatsApp') }}</span></span>
                    </a>
                </div>
            </div>

            <div class="rounded-2xl bg-[#FFFBEB] border border-[#FDE68A] p-4 text-[13px] text-[#78350F] leading-relaxed">
                <p class="font-bold flex items-center gap-1.5 mb-1.5 text-[#92400E]">@svg('lucide-shield-alert', 'w-4 h-4') {{ __('Safety tips') }}</p>
                <ul class="list-disc pl-4 space-y-1">
                    <li>{{ __('Meet the seller in a safe, public place.') }}</li>
                    <li>{{ __('Check the item before you pay.') }}</li>
                    <li>{{ __('Never send an advance payment or share an OTP.') }}</li>
                </ul>
                <a href="{{ route('pages.safety') }}" class="inline-block mt-2 font-semibold text-[#92400E] underline underline-offset-2">{{ __('More safety advice') }}</a>
            </div>

            <x-banner-slot :banner="$banner" placement="listing_sidebar" />

            <a href="{{ route('listings.create') }}" class="tap hidden lg:flex items-center justify-center gap-2 w-full py-3 rounded-xl border-2 border-dashed border-[#C4B5FD] text-brand-dark font-bold text-sm hover:bg-mist">@svg('lucide-plus', 'w-4 h-4') {{ __('Post an ad like this') }}</a>
        </aside>
    </div>

    @if ($similar->isNotEmpty())
        <section class="mt-8" aria-labelledby="similar-title">
            <h2 id="similar-title" class="text-lg sm:text-xl font-extrabold mb-3">{{ __('Similar ads') }}</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                @foreach ($similar as $a)<x-ad-tile :listing="$a" />@endforeach
            </div>
        </section>
    @endif

    @unless ($isOwner)
        {{-- Mobile contact bar --}}
        <div class="lg:hidden fixed bottom-0 inset-x-0 z-50 bg-white/95 backdrop-blur border-t border-line px-3 pt-2.5 pb-[calc(10px+env(safe-area-inset-bottom))] grid grid-cols-3 gap-2">
            <button x-data="phoneReveal('{{ route('listings.phone', $listing) }}', @js(__('Call')))" @click="reveal()" class="tap flex items-center justify-center gap-1.5 py-3 rounded-xl bg-ink text-white text-sm font-bold truncate">@svg('lucide-phone', 'w-4 h-4 shrink-0') <span x-text="label" class="truncate">{{ __('Call') }}</span></button>
            <button @click="{{ auth()->check() ? 'chat = true' : "window.location='".route('login')."'" }}" class="tap flex items-center justify-center gap-1.5 py-3 rounded-xl brand-gradient text-white text-sm font-bold">@svg('lucide-message-square', 'w-4 h-4') {{ __('Chat') }}</button>
            <a href="{{ route('listings.whatsapp', $listing) }}" target="_blank" rel="noopener" class="tap flex items-center justify-center gap-1.5 py-3 rounded-xl bg-wa text-white text-sm font-bold">@svg('lucide-send', 'w-4 h-4') WhatsApp</a>
        </div>
    @endunless

    @unless ($ownAd)
        @auth
            {{-- Chat sheet --}}
            <div x-show="chat" x-cloak @keydown.escape.window="chat = false" @click="chat = false" class="fixed inset-0 z-[80] bg-deep/50 backdrop-blur-[2px] flex items-end sm:items-center justify-center">
                <form @click.stop method="POST" action="{{ route('messages.start', $listing) }}" class="sheet bg-white w-full sm:max-w-md rounded-t-[22px] sm:rounded-2xl shadow-2xl pb-safe" x-data="{ body: '' }">
                    @csrf
                    <div class="flex items-center justify-between px-5 pt-4 pb-3 border-b border-line-soft">
                        <h3 class="text-base font-bold">{{ __('Message :name', ['name' => $listing->sellerName()]) }}</h3>
                        <button type="button" @click="chat = false" class="p-2 -mr-2 rounded-full hover:bg-mist" aria-label="{{ __('Close') }}">@svg('lucide-x', 'w-5 h-5 text-muted')</button>
                    </div>
                    <div class="p-5 space-y-3">
                        <div class="flex flex-wrap gap-2">
                            @foreach ($quick as $qm)<button type="button" @click="body = @js($qm)" class="chip">{{ $qm }}</button>@endforeach
                        </div>
                        <textarea name="body" x-model="body" rows="4" maxlength="1500" required placeholder="{{ __('Write your message…') }}" class="input"></textarea>
                        <p class="text-xs text-muted">{{ __('Keep chats on 4U.lk. Never share OTP codes or pay in advance.') }}</p>
                        <button class="btn-primary w-full py-3" :disabled="body.trim().length < 2">@svg('lucide-send', 'w-4 h-4') {{ __('Send message') }}</button>
                    </div>
                </form>
            </div>
        @endauth
    @endunless
</div>
</x-layouts.app>
