@props(['ad', 'showSpecs' => false])
<div class="card p-3 sm:p-4 flex flex-col md:flex-row gap-3" x-data="{ reject: false }">
    <a href="{{ $ad->url() }}" target="_blank" class="relative w-full md:w-32 h-32 md:h-24 rounded-lg overflow-hidden shrink-0"><x-ad-image :listing="$ad" /></a>
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap gap-1.5 items-center">
            <span class="text-xs text-faint">#{{ $ad->id }}</span>
            <span class="badge bg-slate-100 text-slate-700">{{ strtoupper($ad->trashed() ? 'deleted' : $ad->statusLabel()) }}</span>
            @if ($ad->risk_score)<span class="badge {{ $ad->risk_score >= 40 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800' }}">RISK {{ $ad->risk_score }}</span>@endif
            @if ($ad->reports_count)<span class="badge bg-red-600 text-white">{{ $ad->reports_count }} REPORTS</span>@endif
            @if ($ad->isTop())<span class="badge bg-sun text-ink">TOP</span>@endif
        </div>
        <a href="{{ $ad->url() }}" target="_blank" class="block font-bold mt-0.5 hover:text-brand-dark">{{ $ad->title }}</a>
        <p class="text-sm text-muted">{{ $ad->priceLabel() }} · {{ $ad->category->name }} · {{ $ad->place() }} · {{ $ad->created_at->diffForHumans() }}</p>
        @if ($showSpecs && $ad->specs)
            <p class="text-xs text-muted mt-0.5">
                @foreach (array_slice($ad->specs, 0, 6, true) as $k => $v)<span class="whitespace-nowrap"><span class="text-faint">{{ $k }}:</span> <b class="text-ink font-semibold">{{ $v }}</b></span>@unless ($loop->last) · @endunless @endforeach
            </p>
        @endif
        <p class="text-sm">By <b>{{ $ad->user->name }}</b> ({{ $ad->phone }}) · trust {{ $ad->user->trust_score }} @if ($ad->user->isVerified())· <span class="text-emerald-700 font-semibold">verified</span>@endif</p>
        @if ($ad->flag_reason)<p class="text-xs text-red-600 font-semibold mt-1">⚠ {{ $ad->flag_reason }}</p>@endif
        <p class="text-xs text-muted mt-1 line-clamp-2">{{ $ad->description }}</p>
        <form x-show="reject" x-cloak method="POST" action="{{ route('admin.listings.reject', $ad) }}" class="mt-2 flex flex-wrap gap-2">
            @csrf
            <select name="reason" class="input !py-1.5 !text-sm !w-auto">
                @foreach (['Suspected fraud or scam', 'Duplicate ad', 'Wrong category', 'Misleading price', 'Prohibited item', 'Poor photos or description', 'Contact details in title or photos'] as $r)<option>{{ $r }}</option>@endforeach
            </select>
            <button class="btn-danger !py-1.5">Reject</button>
        </form>
    </div>
    @unless ($ad->trashed())
        <div class="flex md:flex-col gap-2 shrink-0 flex-wrap md:w-36">
            @if ($ad->status !== 'active' || $ad->is_flagged)
                <form method="POST" action="{{ route('admin.listings.approve', $ad) }}">@csrf<button class="btn-primary !py-1.5 w-full">Approve</button></form>
            @endif
            <button @click="reject = !reject" class="btn-ghost !py-1.5">Reject…</button>
            <form method="POST" action="{{ route('admin.listings.promote', $ad) }}" class="flex gap-1">@csrf
                <select name="type" class="input !py-1 !px-2 !text-xs">@foreach (config('marketplace.promotions') as $k => $p)<option value="{{ $k }}">{{ $p['name'] }}</option>@endforeach</select>
                <button class="btn-ghost !py-1 !px-2 text-xs" title="Give promotion for free">+</button>
            </form>
            <form method="POST" action="{{ route('admin.listings.destroy', $ad) }}" onsubmit="return confirm('Delete ad #{{ $ad->id }}?')">@csrf @method('DELETE')<button class="btn-danger !py-1.5 w-full">Delete</button></form>
        </div>
    @endunless
</div>
