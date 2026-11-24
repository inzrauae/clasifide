<x-admin-shell title="Ad categories" active="categories">
    <p class="text-sm text-muted mb-4">Each category has its own back office: an overview, a moderation queue, and settings for its fee and the fields sellers fill in.</p>
    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
        @foreach ($categories as $c)
            <div class="card p-4 flex flex-col {{ $c->is_active ? '' : 'opacity-70' }}">
                <a href="{{ route('admin.categories.show', $c) }}" class="flex items-start gap-3 group">
                    <span class="w-10 h-10 rounded-xl bg-lilac text-brand flex items-center justify-center shrink-0">@svg('lucide-'.$c->icon, 'w-5 h-5')</span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-extrabold group-hover:text-brand-dark truncate">{{ $c->name }}</span>
                        <span class="block text-xs text-muted truncate">{{ $c->hint }}</span>
                    </span>
                    <span class="flex flex-col items-end gap-1 shrink-0">
                        @if ($c->isPremium())<span class="badge bg-[#FFFBEB] text-[#78350F]">Rs {{ number_format($c->posting_fee) }}/ad</span>@endif
                        @unless ($c->is_active)<span class="badge bg-slate-100 text-slate-600">HIDDEN</span>@endunless
                    </span>
                </a>
                <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <a href="{{ route('admin.categories.ads', [$c, 'status' => 'active']) }}" class="rounded-xl bg-mist py-2 hover:brightness-95"><dd class="text-lg font-extrabold tabular-nums">{{ number_format($c->live_count) }}</dd><dt class="text-[11px] text-muted font-semibold">Live</dt></a>
                    <a href="{{ route('admin.categories.ads', [$c, 'status' => 'pending']) }}" class="rounded-xl py-2 hover:brightness-95 {{ $c->pending_count ? 'bg-[#FFFBEB] ring-1 ring-amber-300' : 'bg-mist' }}"><dd class="text-lg font-extrabold tabular-nums">{{ number_format($c->pending_count) }}</dd><dt class="text-[11px] text-muted font-semibold">To review</dt></a>
                    <a href="{{ route('admin.categories.ads', [$c, 'status' => 'flagged']) }}" class="rounded-xl py-2 hover:brightness-95 {{ $c->flagged_count ? 'bg-red-50 ring-1 ring-red-200' : 'bg-mist' }}"><dd class="text-lg font-extrabold tabular-nums">{{ number_format($c->flagged_count) }}</dd><dt class="text-[11px] text-muted font-semibold">Flagged</dt></a>
                </dl>
                <div class="mt-3 pt-3 border-t border-line-soft flex items-center justify-between text-xs">
                    <span class="text-muted">{{ count($c->specFields()) }} spec fields @if ($c->awaiting_fee_count)· {{ $c->awaiting_fee_count }} awaiting fee @endif</span>
                    <span class="flex gap-3 font-bold">
                        <a href="{{ route('admin.categories.show', $c) }}" class="text-brand-dark hover:underline">Overview</a>
                        <a href="{{ route('admin.categories.edit', $c) }}" class="text-brand-dark hover:underline">Settings</a>
                    </span>
                </div>
            </div>
        @endforeach
    </div>
</x-admin-shell>
