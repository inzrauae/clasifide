@php($tabs = ['active' => 'Live', 'pending' => 'Pending', 'rejected' => 'Rejected', 'sold' => 'Sold', 'expired' => 'Expired'])
<x-dashboard-shell title="My ads" active="ads">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-extrabold">My ads</h1>
        <div class="flex items-center gap-3">
            @if ($credits)<span class="text-xs font-bold text-[#4C1D95] bg-mist rounded-full px-3 py-1.5">@svg('lucide-zap', 'w-3.5 h-3.5 inline') {{ $credits }} credits</span>@endif
            <a href="{{ route('listings.create') }}" class="btn-sun !py-2">@svg('lucide-plus', 'w-4 h-4 stroke-[3]') Post ad</a>
        </div>
    </div>

    <div class="mt-4 flex gap-1 overflow-x-auto no-scrollbar border-b border-line">
        @foreach ($tabs as $k => $label)
            <a href="{{ route('dashboard.ads', ['tab' => $k]) }}" class="shrink-0 px-4 py-2.5 text-sm font-bold border-b-2 -mb-px {{ $tab === $k ? 'border-brand text-brand-dark' : 'border-transparent text-muted hover:text-ink' }}">
                {{ $label }} <span class="ml-1 text-xs font-semibold {{ $tab === $k ? 'text-brand' : 'text-faint' }}">{{ $counts[$k] }}</span>
            </a>
        @endforeach
    </div>

    <div class="mt-4 space-y-3">
        @forelse ($listings as $ad)
            <div class="card p-3 sm:p-4 flex flex-col sm:flex-row gap-3 sm:gap-4">
                <a href="{{ $ad->url() }}" class="relative w-full sm:w-36 h-40 sm:h-28 rounded-lg overflow-hidden shrink-0"><x-ad-image :listing="$ad" /></a>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span @class(['badge', 'bg-emerald-100 text-emerald-700' => $ad->isLive(), 'bg-amber-100 text-amber-800' => in_array($ad->status, ['pending', 'pending_payment']), 'bg-red-100 text-red-700' => $ad->status === 'rejected', 'bg-slate-100 text-slate-600' => in_array($ad->status, ['sold', 'expired']) || ($ad->status === 'active' && ! $ad->isLive())])>{{ strtoupper($ad->statusLabel()) }}</span>
                        @if ($ad->isTop())<span class="badge bg-sun text-ink">TOP until {{ $ad->top_until->format('j M') }}</span>@endif
                        @if ($ad->isFeatured())<span class="badge bg-[#EDE9FE] text-brand-dark">FEATURED until {{ $ad->featured_until->format('j M') }}</span>@endif
                        @if ($ad->isUrgent())<span class="badge bg-red-600 text-white">URGENT</span>@endif
                    </div>
                    <a href="{{ $ad->url() }}" class="mt-1 block font-bold leading-snug hover:text-brand-dark line-clamp-2">{{ $ad->title }}</a>
                    <p class="text-sm text-brand-dark font-extrabold">{{ $ad->priceLabel() }}</p>
                    <p class="mt-1 text-xs text-muted flex flex-wrap gap-x-3">
                        <span>@svg('lucide-eye', 'w-3.5 h-3.5 inline') {{ number_format($ad->views_count) }}</span>
                        <span>@svg('lucide-phone', 'w-3.5 h-3.5 inline') {{ $ad->phone_reveals }}</span>
                        <span>@svg('lucide-send', 'w-3.5 h-3.5 inline') {{ $ad->whatsapp_clicks }}</span>
                        <span>@svg('lucide-message-square', 'w-3.5 h-3.5 inline') {{ $ad->messages_count }}</span>
                        <span>@svg('lucide-heart', 'w-3.5 h-3.5 inline') {{ $ad->favorites_count }}</span>
                        @if ($ad->expires_at && $ad->isLive())<span>Expires {{ $ad->expires_at->diffForHumans() }}</span>@endif
                    </p>
                    @if ($ad->status === 'rejected' && $ad->reject_reason)<p class="mt-1 text-xs text-red-600 font-semibold">Reason: {{ $ad->reject_reason }}</p>@endif
                    @if ($ad->status === 'pending' && $ad->is_flagged)<p class="mt-1 text-xs text-amber-700">Our safety team is checking this ad. This usually takes a few hours.</p>@endif
                </div>
                <div class="flex sm:flex-col gap-2 sm:w-36 shrink-0 flex-wrap">
                    @if ($ad->status === 'pending_payment')
                        <a href="{{ route('listings.payFee', $ad) }}" class="btn-sun !py-2 flex-1">Pay Rs {{ number_format($ad->category->posting_fee) }}</a>
                        @if (($o = $pendingOrders[$ad->id] ?? null) && $o->status === 'awaiting_review')<p class="text-[11px] text-muted">Slip under review</p>@endif
                    @elseif ($ad->isLive())
                        <a href="{{ route('promote.show', $ad) }}" class="btn-primary !py-2 flex-1">@svg('lucide-rocket', 'w-4 h-4') Promote</a>
                    @endif
                    @if (($ad->status === 'expired') || ($ad->status === 'active' && ! $ad->isLive()))
                        <form method="POST" action="{{ route('listings.renew', $ad) }}" class="flex-1">@csrf<button class="btn-primary !py-2 w-full">Renew free</button></form>
                    @endif
                    <a href="{{ route('listings.edit', $ad) }}" class="btn-ghost !py-2 flex-1">@svg('lucide-pencil', 'w-4 h-4') Edit</a>
                    @if ($ad->isLive())
                        <form method="POST" action="{{ route('listings.sold', $ad) }}" class="flex-1">@csrf<button class="btn-ghost !py-2 w-full">Mark sold</button></form>
                    @endif
                    <form method="POST" action="{{ route('listings.destroy', $ad) }}" onsubmit="return confirm('Delete this ad? This cannot be undone.')" class="flex-1">@csrf @method('DELETE')<button class="btn-danger !py-2 w-full">@svg('lucide-trash-2', 'w-4 h-4') Delete</button></form>
                </div>
            </div>
        @empty
            <div class="card p-10 text-center">
                <p class="font-extrabold text-lg">Nothing here yet</p>
                <p class="text-sm text-muted mt-1">Ads you post will show up here with their views and contacts.</p>
                <a href="{{ route('listings.create') }}" class="btn-primary mt-4">Post an ad</a>
            </div>
        @endforelse
    </div>
    <div class="mt-5">{{ $listings->links() }}</div>
</x-dashboard-shell>
