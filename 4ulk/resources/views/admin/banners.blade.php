<x-admin-shell title="Banner advertising" active="banners">
    <x-admin-tabs route="admin.banners" :current="$status" :tabs="['all' => 'All', 'pending' => 'To approve', 'awaiting_payment' => 'Awaiting payment', 'active' => 'Active', 'expired' => 'Expired', 'rejected' => 'Rejected']" />

    <details class="card p-4 mb-4">
        <summary class="font-bold cursor-pointer">+ Add a banner directly</summary>
        <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="mt-3 grid sm:grid-cols-3 gap-3">
            @csrf
            <input name="advertiser" required placeholder="Advertiser" class="input">
            <select name="placement" class="input">@foreach ($placements as $k => $p)<option value="{{ $k }}">{{ $p['name'] }}</option>@endforeach</select>
            <input name="url" type="url" placeholder="https:// link" class="input">
            <input name="starts_at" type="date" required value="{{ today()->toDateString() }}" class="input">
            <input name="ends_at" type="date" required value="{{ today()->addWeek()->toDateString() }}" class="input">
            <input name="price" type="number" min="0" placeholder="Price charged (Rs)" class="input">
            <input name="image" type="file" accept="image/*" required class="input !py-2 sm:col-span-2">
            <button class="btn-primary">Create & publish</button>
        </form>
    </details>

    <div class="space-y-3">
        @forelse ($banners as $b)
            <form method="POST" action="{{ route('admin.banners.update', $b) }}" enctype="multipart/form-data" class="card p-4 grid md:grid-cols-[200px_1fr_auto] gap-4 items-start">
                @csrf
                <div class="rounded-lg bg-mist aspect-[3/1] overflow-hidden flex items-center justify-center text-xs text-faint">
                    @if ($b->image)<img src="{{ asset($b->image) }}" class="w-full h-full object-cover" alt="">@else No artwork yet @endif
                </div>
                <div class="text-sm space-y-1">
                    <p class="font-bold">{{ $b->advertiser }} <span class="text-xs text-muted font-normal">· {{ $b->contact_email }} · {{ $b->contact_phone }}</span></p>
                    <p class="text-muted">{{ $b->placementLabel() }} · {{ $b->starts_at?->format('j M') }} – {{ $b->ends_at?->format('j M Y') }} · Rs {{ number_format($b->price) }}</p>
                    <p class="text-muted">{{ number_format($b->impressions) }} impressions · {{ number_format($b->clicks) }} clicks{{ $b->impressions ? ' · CTR '.round($b->clicks / $b->impressions * 100, 2).'%' : '' }}</p>
                    @if ($b->alt)<p class="text-xs">“{{ $b->alt }}”</p>@endif
                    <div class="flex flex-wrap gap-2 pt-1">
                        <input type="date" name="starts_at" value="{{ $b->starts_at?->toDateString() }}" class="input !py-1 !text-xs !w-auto">
                        <input type="date" name="ends_at" value="{{ $b->ends_at?->toDateString() }}" class="input !py-1 !text-xs !w-auto">
                        <input type="url" name="url" value="{{ $b->url }}" placeholder="Link" class="input !py-1 !text-xs !w-48">
                        <input type="file" name="image" accept="image/*" class="text-xs">
                    </div>
                </div>
                <div class="flex md:flex-col gap-2">
                    <select name="status" class="input !py-1.5 !text-sm">@foreach (['pending', 'awaiting_payment', 'active', 'rejected', 'expired'] as $s)<option value="{{ $s }}" @selected($b->status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>@endforeach</select>
                    <button class="btn-primary !py-1.5">Save</button>
                </div>
            </form>
        @empty
            <p class="card p-10 text-center text-muted">No banners yet.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $banners->links() }}</div>
</x-admin-shell>
