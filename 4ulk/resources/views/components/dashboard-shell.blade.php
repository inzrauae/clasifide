@props(['title', 'active' => 'dashboard'])
@php
    $u = auth()->user();
    $sub = $u->activeSubscription;
    $nav = [
        'dashboard' => ['Overview', 'lucide-chart-column', route('dashboard')],
        'ads' => ['My ads', 'lucide-tag', route('dashboard.ads')],
        'messages' => ['Messages', 'lucide-inbox', route('messages.index')],
        'leads' => ['Buyer inquiries', 'lucide-users-round', route('dashboard.leads')],
        'saved' => ['Saved ads', 'lucide-heart', route('dashboard.saved')],
        'store' => ['My store', 'lucide-store', route('dashboard.store')],
        'billing' => ['Package & billing', 'lucide-credit-card', route('dashboard.billing')],
        'profile' => ['Profile', 'lucide-settings', route('dashboard.profile')],
    ];
@endphp
<x-layouts.app :title="$title">
<div class="max-w-6xl mx-auto px-4 py-4 sm:py-6">
    <div class="grid grid-cols-[minmax(0,1fr)] lg:grid-cols-[240px_1fr] gap-5 items-start">
        <aside class="lg:sticky lg:top-[88px] space-y-3">
            <div class="card p-4 flex items-center gap-3">
                <span class="w-11 h-11 rounded-full brand-gradient text-white font-bold flex items-center justify-center overflow-hidden shrink-0">
                    @if ($u->avatar)<img src="{{ asset($u->avatar) }}" alt="" class="w-full h-full object-cover">@else{{ $u->initials() }}@endif
                </span>
                <div class="min-w-0">
                    <p class="font-bold truncate">{{ $u->name }}</p>
                    <p class="text-xs text-muted flex items-center gap-1">
                        {{ $sub ? $sub->package->name.' package' : 'Free plan' }}
                        @if ($u->isVerified()) · <span class="text-emerald-700 font-semibold">Verified</span>@endif
                    </p>
                </div>
            </div>
            <nav class="card p-2 flex lg:flex-col overflow-x-auto no-scrollbar gap-0.5 text-sm font-semibold" aria-label="Dashboard">
                @foreach ($nav as $key => [$label, $icon, $href])
                    <a href="{{ $href }}" class="shrink-0 flex items-center gap-2.5 px-3 py-2.5 rounded-xl {{ $active === $key ? 'bg-[#EDE9FE] text-brand-dark' : 'text-ink hover:bg-mist' }}">@svg($icon, 'w-4 h-4') {{ $label }}</a>
                @endforeach
                <form method="POST" action="{{ route('logout') }}" class="lg:hidden shrink-0">@csrf<button class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-muted">@svg('lucide-log-out', 'w-4 h-4') Sign out</button></form>
            </nav>
            @unless ($sub)
                <a href="{{ route('pricing') }}" class="hidden lg:block rounded-2xl bg-night text-white p-4 hover:brightness-110">
                    <p class="font-extrabold flex items-center gap-1.5">@svg('lucide-crown', 'w-4 h-4 text-sun') Grow your sales</p>
                    <p class="text-xs text-white/70 mt-1">Store, promotions & analytics from Rs 990/month.</p>
                </a>
            @endunless
        </aside>
        <section class="min-w-0">{{ $slot }}</section>
    </div>
</div>
</x-layouts.app>
