@props(['ad', 'pendingOrder' => null, 'columns' => []])
<div class="card p-3 sm:p-4 flex flex-col sm:flex-row gap-3 sm:gap-4">
    <a href="{{ $ad->url() }}" class="relative w-full sm:w-36 h-40 sm:h-28 rounded-lg overflow-hidden shrink-0"><x-ad-image :listing="$ad" /></a>
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-1.5">
            <span @class(['badge', 'bg-emerald-100 text-emerald-700' => $ad->isLive(), 'bg-amber-100 text-amber-800' => in_array($ad->status, ['pending', 'pending_payment']), 'bg-red-100 text-red-700' => $ad->status === 'rejected', 'bg-slate-100 text-slate-600' => in_array($ad->status, ['sold', 'expired']) || ($ad->status === 'active' && ! $ad->isLive())])>{{ strtoupper($ad->statusLabel()) }}</span>
            @if ($ad->isTop())<span class="badge bg-sun text-ink">{{ __('TOP until :date', ['date' => $ad->top_until->translatedFormat('j M')]) }}</span>@endif
            @if ($ad->isFeatured())<span class="badge bg-[#EDE9FE] text-brand-dark">{{ __('FEATURED until :date', ['date' => $ad->featured_until->translatedFormat('j M')]) }}</span>@endif
            @if ($ad->isUrgent())<span class="badge bg-red-600 text-white">{{ __('URGENT') }}</span>@endif
        </div>
        <a href="{{ $ad->url() }}" class="mt-1 block font-bold leading-snug hover:text-brand-dark line-clamp-2">{{ $ad->title }}</a>
        <p class="text-sm text-brand-dark font-extrabold">{{ $ad->priceLabel() }}</p>
        @if ($columns)
            <dl class="mt-1 flex flex-wrap gap-x-4 gap-y-0.5 text-xs">
                @foreach ($columns as $col)
                    <div class="flex gap-1"><dt class="text-faint">{{ $col }}</dt><dd class="font-semibold {{ isset($ad->specs[$col]) ? '' : 'text-faint' }}">{{ $ad->specs[$col] ?? '—' }}</dd></div>
                @endforeach
            </dl>
        @endif
        <p class="mt-1 text-xs text-muted flex flex-wrap gap-x-3">
            <span>@svg('lucide-eye', 'w-3.5 h-3.5 inline') {{ number_format($ad->views_count) }}</span>
            <span>@svg('lucide-phone', 'w-3.5 h-3.5 inline') {{ $ad->phone_reveals }}</span>
            <span>@svg('lucide-send', 'w-3.5 h-3.5 inline') {{ $ad->whatsapp_clicks }}</span>
            <span>@svg('lucide-message-square', 'w-3.5 h-3.5 inline') {{ $ad->messages_count }}</span>
            <span>@svg('lucide-heart', 'w-3.5 h-3.5 inline') {{ $ad->favorites_count }}</span>
            @if ($ad->expires_at && $ad->isLive())<span>{{ __('Expires :when', ['when' => $ad->expires_at->diffForHumans()]) }}</span>@endif
        </p>
        {{ $slot }}
        @if ($ad->status === 'rejected' && $ad->reject_reason)<p class="mt-1 text-xs text-red-600 font-semibold">{{ __('Reason: :reason', ['reason' => $ad->reject_reason]) }}</p>@endif
        @if ($ad->status === 'pending' && $ad->is_flagged)<p class="mt-1 text-xs text-amber-700">{{ __('Our safety team is checking this ad. This usually takes a few hours.') }}</p>@endif
    </div>
    <div class="flex sm:flex-col gap-2 sm:w-36 shrink-0 flex-wrap">
        @if ($ad->status === 'pending_payment')
            <a href="{{ route('listings.payFee', $ad) }}" class="btn-sun !py-2 flex-1">{{ __('Pay Rs :amount', ['amount' => number_format($ad->category->posting_fee)]) }}</a>
            @if ($pendingOrder && $pendingOrder->status === 'awaiting_review')<p class="text-[11px] text-muted">{{ __('Slip under review') }}</p>@endif
        @elseif ($ad->isLive())
            <a href="{{ route('promote.show', $ad) }}" class="btn-primary !py-2 flex-1">@svg('lucide-rocket', 'w-4 h-4') {{ __('Promote') }}</a>
        @endif
        @if (($ad->status === 'expired') || ($ad->status === 'active' && ! $ad->isLive()))
            <form method="POST" action="{{ route('listings.renew', $ad) }}" class="flex-1">@csrf<button class="btn-primary !py-2 w-full">{{ __('Renew free') }}</button></form>
        @endif
        <a href="{{ route('listings.edit', $ad) }}" class="btn-ghost !py-2 flex-1">@svg('lucide-pencil', 'w-4 h-4') {{ __('Edit') }}</a>
        @if ($ad->isLive())
            <form method="POST" action="{{ route('listings.sold', $ad) }}" class="flex-1">@csrf<button class="btn-ghost !py-2 w-full">{{ __('Mark sold') }}</button></form>
        @endif
        <form method="POST" action="{{ route('listings.destroy', $ad) }}" onsubmit="return confirm(@js(__('Delete this ad? This cannot be undone.')))" class="flex-1">@csrf @method('DELETE')<button class="btn-danger !py-2 w-full">@svg('lucide-trash-2', 'w-4 h-4') {{ __('Delete') }}</button></form>
    </div>
</div>
