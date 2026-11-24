@props(['title' => null, 'description' => null, 'canonical' => null, 'ogImage' => null, 'hideBottomNav' => false])
<!DOCTYPE html>
@php($alt = \App\Support\Lang::alternates())
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' | 4U.lk' : __('4U.lk — Buy & sell anything in Sri Lanka') }}</title>
    <meta name="description" content="{{ $description ?? __('Buy and sell vehicles, property, phones, electronics and more, find jobs and services across all 25 districts of Sri Lanka on 4U.lk.') }}">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    @foreach ($alt as $code => $href)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ $href }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $alt['en'] }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/brand/favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/brand/app-icon-512.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#1E0F45">
    <meta property="og:site_name" content="4U.lk">
    <meta property="og:title" content="{{ $title ?? __('4U.lk — Everything. Just for you.') }}">
    <meta property="og:description" content="{{ $description ?? __('Buy & sell anything across Sri Lanka.') }}">
    <meta property="og:locale" content="{{ app()->getLocale() === 'si' ? 'si_LK' : 'en_LK' }}">
    <meta property="og:image" content="{{ $ogImage ?? asset('assets/brand/og-image.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    @stack('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800{{ app()->getLocale() === 'si' ? '&family=Noto+Sans+Sinhala:wght@400;500;600;700;800' : '' }}&display=swap" rel="stylesheet">
    <script>window.i18n = @json(app()->getLocale() === 'si' ? \App\Support\Lang::jsStrings() : (object) []);</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-page text-ink font-sans antialiased overflow-x-clip">
@php($authUser = auth()->user())
@php($unread = $authUser?->unreadMessagesCount() ?? 0)

<header class="sticky top-0 z-40 bg-night text-white shadow-[0_6px_20px_-12px_rgba(14,8,32,.8)]" x-data="{ menu: false }">
    <div class="max-w-6xl mx-auto px-4 h-14 lg:h-[68px] flex items-center gap-3">
        <a href="{{ route('home') }}" aria-label="{{ __('4U.lk home') }}" class="shrink-0"><x-logo light class="h-8 lg:h-9 w-auto" /></a>

        <nav class="hidden md:flex items-center gap-1 ml-2 text-[14px] font-semibold" aria-label="{{ __('Main') }}">
            <a href="{{ route('listings.index') }}" class="px-3 py-2 rounded-lg hover:bg-white/10 text-white/80">{{ __('All ads') }}</a>
            <a href="{{ route('listings.index', ['cat' => 'vehicles']) }}" class="px-3 py-2 rounded-lg hover:bg-white/10 text-white/80">{{ __('Vehicles') }}</a>
            <a href="{{ route('listings.index', ['cat' => 'property']) }}" class="px-3 py-2 rounded-lg hover:bg-white/10 text-white/80">{{ __('Property') }}</a>
            <a href="{{ route('pricing') }}" class="px-3 py-2 rounded-lg hover:bg-white/10 text-white/80">{{ __('For business') }}</a>
        </nav>

        <div class="ml-auto flex items-center gap-1.5">
            @php($other = app()->getLocale() === 'si' ? 'en' : 'si')
            <a href="{{ $alt[$other] }}" hreflang="{{ $other }}" lang="{{ $other }}" class="px-2.5 py-2 rounded-lg hover:bg-white/10 text-[13px] font-bold text-white/85 border border-white/15" title="{{ $other === 'si' ? 'සිංහලෙන් බලන්න' : 'View in English' }}">{{ \App\Support\Lang::NAMES[$other] }}</a>
            @auth
                <a href="{{ route('messages.index') }}" class="relative hidden md:flex p-2.5 rounded-lg hover:bg-white/10" aria-label="{{ __('Messages') }}">
                    @svg('lucide-message-square', 'w-5 h-5')
                    @if ($unread)<span class="absolute top-1 right-1 min-w-4 h-4 px-1 rounded-full bg-sun text-ink text-[10px] font-extrabold flex items-center justify-center">{{ $unread }}</span>@endif
                </a>
                <div class="relative hidden md:block" @click.outside="menu = false">
                    <button @click="menu = !menu" class="flex items-center gap-2 pl-1.5 pr-2.5 py-1.5 rounded-lg hover:bg-white/10 text-sm font-semibold">
                        <span class="w-8 h-8 rounded-full brand-gradient flex items-center justify-center text-xs font-bold overflow-hidden">
                            @if ($authUser->avatar)<img src="{{ asset($authUser->avatar) }}" alt="" class="w-full h-full object-cover">@else{{ $authUser->initials() }}@endif
                        </span>
                        <span class="max-w-28 truncate">{{ \Illuminate\Support\Str::before($authUser->name, ' ') ?: $authUser->name }}</span>
                        @svg('lucide-chevron-down', 'w-4 h-4 text-white/60')
                    </button>
                    <div x-show="menu" x-cloak x-transition.origin.top.right class="absolute right-0 mt-2 w-60 rounded-2xl bg-white text-ink shadow-2xl border border-line py-2 text-sm">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-mist">@svg('lucide-layout-dashboard', 'w-4 h-4 text-brand') {{ __('Seller dashboard') }}</a>
                        <a href="{{ route('dashboard.ads') }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-mist">@svg('lucide-tag', 'w-4 h-4 text-brand') {{ __('My ads') }}</a>
                        <a href="{{ route('messages.index') }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-mist">@svg('lucide-inbox', 'w-4 h-4 text-brand') {{ __('Messages') }} @if($unread)<span class="ml-auto badge bg-sun text-ink">{{ $unread }}</span>@endif</a>
                        <a href="{{ route('dashboard.saved') }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-mist">@svg('lucide-heart', 'w-4 h-4 text-brand') {{ __('Saved ads') }}</a>
                        <a href="{{ route('dashboard.store') }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-mist">@svg('lucide-store', 'w-4 h-4 text-brand') {{ __('My store') }}</a>
                        <a href="{{ route('dashboard.billing') }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-mist">@svg('lucide-credit-card', 'w-4 h-4 text-brand') {{ __('Package & billing') }}</a>
                        <a href="{{ route('dashboard.profile') }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-mist">@svg('lucide-settings', 'w-4 h-4 text-brand') {{ __('Profile') }}</a>
                        @if ($authUser->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-mist font-bold text-brand-dark">@svg('lucide-shield-check', 'w-4 h-4') {{ __('Admin panel') }}</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-line-soft mt-1 pt-1">@csrf
                            <button class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-mist text-left text-muted">@svg('lucide-log-out', 'w-4 h-4') {{ __('Sign out') }}</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="hidden sm:inline-flex px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-semibold text-white/85">{{ __('Sign in') }}</a>
            @endauth
            <a href="{{ route('listings.create') }}" class="tap inline-flex items-center gap-1.5 px-3.5 lg:px-4 py-2 lg:py-2.5 rounded-xl bg-sun hover:bg-sun-dark text-ink text-sm font-extrabold">
                @svg('lucide-plus', 'w-4 h-4 stroke-[3]') <span>{{ __('Post ad') }}</span>
            </a>
        </div>
    </div>
</header>

@if (session('status') || session('error'))
    <div class="max-w-6xl mx-auto px-4 mt-4">
        @if (session('status'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm font-semibold flex gap-2 items-start">@svg('lucide-circle-check', 'w-5 h-5 shrink-0') <span>{{ session('status') }}</span></div>
        @endif
        @if (session('error'))
            <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm font-semibold flex gap-2 items-start">@svg('lucide-circle-alert', 'w-5 h-5 shrink-0') <span>{{ session('error') }}</span></div>
        @endif
    </div>
@endif

<main class="pb-24 lg:pb-10">
    {{ $slot }}
</main>

<footer class="hidden lg:block bg-night text-white/70 mt-8">
    <div class="max-w-6xl mx-auto px-4 py-10 grid grid-cols-4 gap-8 text-sm">
        <div>
            <x-logo light class="h-9 w-auto" />
            <p class="mt-3 text-white/60">{{ __('A smarter marketplace. More opportunities for everyone in Sri Lanka.') }}</p>
        </div>
        <div>
            <p class="font-bold text-white mb-3">{{ __('Buy & sell') }}</p>
            <ul class="space-y-2">
                <li><a href="{{ route('listings.index') }}" class="hover:text-white">{{ __('All ads') }}</a></li>
                <li><a href="{{ route('listings.create') }}" class="hover:text-white">{{ __('Post an ad') }}</a></li>
                <li><a href="{{ route('pages.safety') }}" class="hover:text-white">{{ __('Stay safe') }}</a></li>
            </ul>
        </div>
        <div>
            <p class="font-bold text-white mb-3">{{ __('For business') }}</p>
            <ul class="space-y-2">
                <li><a href="{{ route('pricing') }}" class="hover:text-white">{{ __('Seller packages') }}</a></li>
                <li><a href="{{ route('pricing') }}#promote" class="hover:text-white">{{ __('Promote your ads') }}</a></li>
                <li><a href="{{ route('advertise') }}" class="hover:text-white">{{ __('Banner advertising') }}</a></li>
            </ul>
        </div>
        <div>
            <p class="font-bold text-white mb-3">4U.lk</p>
            <ul class="space-y-2">
                <li><a href="{{ route('pages.about') }}" class="hover:text-white">{{ __('About us') }}</a></li>
                <li><a href="{{ route('pages.terms') }}" class="hover:text-white">{{ __('Terms of use') }}</a></li>
                <li><a href="{{ route('pages.privacy') }}" class="hover:text-white">{{ __('Privacy') }}</a></li>
                <li><a href="mailto:{{ config('marketplace.support_email') }}" class="hover:text-white">{{ config('marketplace.support_email') }}</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10 py-4 text-center text-xs text-white/40">&copy; {{ date('Y') }} 4U.lk · {{ __('Made in Sri Lanka') }}</div>
</footer>

{{-- Mobile bottom navigation --}}
@unless ($hideBottomNav)
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur border-t border-line pb-safe" aria-label="{{ __('Mobile') }}">
    <div class="grid grid-cols-5 h-16 text-[11px] font-semibold text-muted">
        <a href="{{ route('home') }}" class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('home') ? 'text-brand' : '' }}">@svg('lucide-house', 'w-5 h-5') {{ __('Home') }}</a>
        <a href="{{ route('listings.index') }}" class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('listings.index') ? 'text-brand' : '' }}">@svg('lucide-search', 'w-5 h-5') {{ __('Search') }}</a>
        <a href="{{ route('listings.create') }}" class="flex flex-col items-center justify-center -mt-5">
            <span class="w-13 h-13 w-[52px] h-[52px] rounded-full bg-sun text-ink flex items-center justify-center shadow-lg ring-4 ring-white">@svg('lucide-plus', 'w-6 h-6 stroke-[3]')</span>
            <span class="mt-0.5 text-ink">{{ __('Post') }}</span>
        </a>
        <a href="{{ route('messages.index') }}" class="relative flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('messages.*') ? 'text-brand' : '' }}">
            @svg('lucide-message-square', 'w-5 h-5') {{ __('Chat') }}
            @if ($unread)<span class="absolute top-2 left-1/2 ml-1.5 min-w-4 h-4 px-1 rounded-full bg-red-500 text-white text-[10px] flex items-center justify-center">{{ $unread }}</span>@endif
        </a>
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('dashboard*') ? 'text-brand' : '' }}">@svg('lucide-circle-user', 'w-5 h-5') {{ __('Account') }}</a>
    </div>
</nav>
@endunless

<div x-data x-show="$store.toast.msg" x-cloak x-transition class="fixed z-[90] bottom-24 lg:bottom-8 left-1/2 -translate-x-1/2 px-4 py-2.5 rounded-full bg-ink text-white text-sm font-semibold shadow-xl" x-text="$store.toast.msg" role="status"></div>

@stack('scripts')
</body>
</html>
