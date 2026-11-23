@props(['title', 'active' => 'dashboard'])
@php($nav = [
    'dashboard' => ['Overview', 'lucide-layout-dashboard', route('admin.dashboard')],
    'listings' => ['Ads & moderation', 'lucide-tag', route('admin.listings')],
    'reports' => ['Reports', 'lucide-flag', route('admin.reports')],
    'users' => ['Users & sellers', 'lucide-users-round', route('admin.users')],
    'orders' => ['Payments', 'lucide-credit-card', route('admin.orders')],
    'banners' => ['Banners', 'lucide-megaphone', route('admin.banners')],
    'packages' => ['Packages', 'lucide-crown', route('admin.packages')],
    'categories' => ['Categories & fees', 'lucide-sliders-horizontal', route('admin.categories')],
])
<x-layouts.app :title="'Admin · '.$title">
<div class="max-w-7xl mx-auto px-4 py-4 sm:py-6">
    <div class="grid grid-cols-[minmax(0,1fr)] lg:grid-cols-[220px_1fr] gap-5 items-start">
        <nav class="card p-2 flex lg:flex-col overflow-x-auto no-scrollbar gap-0.5 text-sm font-semibold lg:sticky lg:top-[88px]" aria-label="Admin">
            <p class="hidden lg:block px-3 pt-2 pb-1 text-[11px] font-bold uppercase tracking-wider text-faint">Admin</p>
            @foreach ($nav as $key => [$label, $icon, $href])
                <a href="{{ $href }}" class="shrink-0 flex items-center gap-2.5 px-3 py-2.5 rounded-xl {{ $active === $key ? 'bg-[#EDE9FE] text-brand-dark' : 'hover:bg-mist' }}">@svg($icon, 'w-4 h-4') {{ $label }}</a>
            @endforeach
        </nav>
        <section class="min-w-0">
            <h1 class="text-2xl font-extrabold mb-4">{{ $title }}</h1>
            {{ $slot }}
        </section>
    </div>
</div>
</x-layouts.app>
