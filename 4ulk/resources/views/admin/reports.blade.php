<x-admin-shell title="Reports" active="reports">
    <x-admin-tabs route="admin.reports" :current="$status" :tabs="['open' => 'Open', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed']" />
    <div class="card divide-y divide-line-soft">
        @forelse ($reports as $r)
            <div class="p-4 flex flex-col md:flex-row gap-3 md:items-center">
                <div class="flex-1 min-w-0">
                    <p class="text-sm"><span class="badge bg-red-100 text-red-700">{{ strtoupper($r->reasonLabel()) }}</span> <span class="text-faint text-xs">{{ $r->created_at->diffForHumans() }} · {{ $r->user?->name ?? 'Guest '.$r->ip }}</span></p>
                    @if ($r->listing)
                        <a href="{{ $r->listing->url() }}" target="_blank" class="block font-bold mt-1 hover:text-brand-dark truncate">#{{ $r->listing_id }} {{ $r->listing->title }}</a>
                        <p class="text-xs text-muted">Seller: {{ $r->listing->user?->name }} · {{ $r->listing->statusLabel() }}</p>
                    @endif
                    @if ($r->details)<p class="text-sm mt-1 text-[#334155]">“{{ $r->details }}”</p>@endif
                </div>
                @if ($r->status === 'open')
                    <form method="POST" action="{{ route('admin.reports.update', $r) }}" class="flex gap-2 shrink-0">@csrf
                        <button name="action" value="remove_ad" class="btn-danger !py-1.5">Take ad down</button>
                        <button name="action" value="resolve" class="btn-ghost !py-1.5">Resolved</button>
                        <button name="action" value="dismiss" class="btn-ghost !py-1.5">Dismiss</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="p-10 text-center text-muted">No reports.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $reports->links() }}</div>
</x-admin-shell>
