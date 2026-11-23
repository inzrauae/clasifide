@php($types = \App\Models\Order::TYPES)
<x-admin-shell title="Overview" active="dashboard">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ([
            ['Revenue this month', 'Rs '.number_format($stats['revenue_month']), route('admin.orders', ['status' => 'paid'])],
            ['Live ads', number_format($stats['live']), route('admin.listings', ['status' => 'active'])],
            ['Paying sellers', number_format($stats['subscribers']), route('admin.users')],
            ['Users', number_format($stats['users']).' (+'.$stats['users_week'].' this week)', route('admin.users')],
        ] as [$l, $v, $href])
            <a href="{{ $href }}" class="card p-4 hover:shadow-md"><p class="text-xs font-semibold text-muted">{{ $l }}</p><p class="mt-1 text-xl font-extrabold">{{ $v }}</p></a>
        @endforeach
    </div>

    <h2 class="mt-6 font-extrabold">Needs action</h2>
    <div class="mt-2 grid grid-cols-2 md:grid-cols-5 gap-3">
        @foreach ([
            ['Ads to review', $stats['pending'], route('admin.listings', ['status' => 'pending']), 'lucide-clock'],
            ['Flagged by safety checks', $stats['flagged'], route('admin.listings', ['status' => 'flagged']), 'lucide-shield-alert'],
            ['Open reports', $stats['reports'], route('admin.reports'), 'lucide-flag'],
            ['Bank slips', $stats['slips'], route('admin.orders'), 'lucide-landmark'],
            ['Banners to approve', $stats['banners'], route('admin.banners', ['status' => 'pending']), 'lucide-megaphone'],
        ] as [$l, $v, $href, $icon])
            <a href="{{ $href }}" class="card p-4 hover:shadow-md {{ $v ? 'ring-2 ring-amber-300' : '' }}">
                <p class="text-xs font-semibold text-muted flex items-center gap-1.5">@svg($icon, 'w-4 h-4 text-brand') {{ $l }}</p>
                <p class="mt-1 text-2xl font-extrabold">{{ $v }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid md:grid-cols-2 gap-4">
        <div class="card p-4">
            <h2 class="font-extrabold">Revenue by stream · {{ now()->format('F') }}</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach ($types as $k => $label)
                    <li class="flex justify-between"><span>{{ $label }}</span><span class="font-bold tabular-nums">Rs {{ number_format($revenueByType[$k]->total ?? 0) }} <span class="text-faint font-normal">({{ $revenueByType[$k]->n ?? 0 }})</span></span></li>
                @endforeach
                <li class="flex justify-between border-t border-line-soft pt-2 font-extrabold"><span>All time</span><span>Rs {{ number_format($stats['revenue_total']) }}</span></li>
            </ul>
        </div>
        <div class="card p-4">
            <h2 class="font-extrabold">Highest-risk ads waiting</h2>
            <ul class="mt-2 divide-y divide-line-soft text-sm">
                @forelse ($flagged as $ad)
                    <li class="py-2 flex justify-between gap-3">
                        <a href="{{ $ad->url() }}" class="truncate hover:text-brand-dark">{{ $ad->title }}<span class="block text-xs text-muted truncate">{{ $ad->flag_reason ?: 'Manual review' }}</span></a>
                        <span class="badge {{ $ad->risk_score >= 40 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800' }} self-center">RISK {{ $ad->risk_score }}</span>
                    </li>
                @empty
                    <li class="py-4 text-muted">Queue is empty.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-4 card overflow-x-auto">
        <h2 class="font-extrabold p-4 pb-0">Latest orders</h2>
        <table class="w-full text-sm mt-2">
            <tbody class="divide-y divide-line-soft">
                @foreach ($recentOrders as $o)
                    <tr><td class="p-3 font-mono text-xs">{{ $o->reference }}</td><td class="p-3">{{ $o->user?->name }}</td><td class="p-3">{{ $o->description }}</td><td class="p-3 font-bold">Rs {{ number_format($o->amount) }}</td><td class="p-3 text-xs uppercase font-bold">{{ str_replace('_', ' ', $o->status) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin-shell>
