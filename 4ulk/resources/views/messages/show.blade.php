@php($me = auth()->user())
@php($archived = $role === 'seller' ? $conversation->seller_archived : $conversation->buyer_archived)
<x-dashboard-shell :title="'Chat with '.$other->name" active="messages">
    <div class="grid xl:grid-cols-[1fr_280px] gap-4 items-start">
        <div class="card flex flex-col h-[calc(100dvh-220px)] min-h-[420px]">
            {{-- Header --}}
            <div class="p-3 sm:p-4 border-b border-line-soft flex items-center gap-3">
                <a href="{{ route('messages.index') }}" class="p-1.5 rounded-lg hover:bg-mist" aria-label="Back">@svg('lucide-chevron-left', 'w-5 h-5')</a>
                <span class="w-10 h-10 rounded-full brand-gradient text-white text-sm font-bold flex items-center justify-center shrink-0">{{ $other->initials() }}</span>
                <div class="min-w-0 flex-1">
                    <p class="font-bold truncate">{{ $other->name }} @if ($other->isVerified())@svg('lucide-badge-check', 'w-4 h-4 inline text-emerald-600')@endif</p>
                    <p class="text-xs text-muted">{{ $other->last_seen_at ? 'Active '.$other->last_seen_at->diffForHumans() : 'Member since '.$other->created_at->format('Y') }}</p>
                </div>
                <form method="POST" action="{{ route('messages.update', $conversation) }}">@csrf @method('PATCH')
                    <input type="hidden" name="archive" value="{{ $archived ? 0 : 1 }}">
                    <button class="p-2 rounded-lg hover:bg-mist text-muted" title="{{ $archived ? 'Unarchive' : 'Archive' }}">@svg('lucide-archive', 'w-4 h-4')</button>
                </form>
            </div>
            @if ($conversation->listing)
                <a href="{{ $conversation->listing->url() }}" class="flex items-center gap-3 px-4 py-2.5 bg-[#FAF9FF] border-b border-line-soft hover:bg-mist">
                    <div class="relative w-10 h-10 rounded-lg overflow-hidden shrink-0"><x-ad-image :listing="$conversation->listing" /></div>
                    <div class="min-w-0"><p class="text-sm font-semibold truncate">{{ $conversation->listing->title }}</p><p class="text-xs font-extrabold text-brand-dark">{{ $conversation->listing->priceLabel() }}</p></div>
                </a>
            @endif

            {{-- Thread --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-2" x-data x-init="$el.scrollTop = $el.scrollHeight">
                @foreach ($conversation->messages as $m)
                    @php($mine = $m->sender_id === $me->id)
                    <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[80%] rounded-2xl px-3.5 py-2 text-[14.5px] leading-snug {{ $mine ? 'brand-gradient text-white rounded-br-md' : 'bg-mist text-ink rounded-bl-md' }}">
                            @if ($m->is_flagged && ! $mine)
                                <p class="text-[11px] font-bold text-red-600 mb-1 flex items-center gap-1">@svg('lucide-shield-alert', 'w-3.5 h-3.5') Be careful: this message may be a scam. Never pay in advance.</p>
                            @endif
                            <p class="whitespace-pre-line break-words">{{ $m->body }}</p>
                            <p class="mt-0.5 text-[10.5px] {{ $mine ? 'text-white/70 text-right' : 'text-faint' }}">{{ $m->created_at->format('j M, g:i a') }}@if ($mine && $m->read_at) · Seen @endif</p>
                        </div>
                    </div>
                @endforeach
                <div id="bottom"></div>
            </div>

            {{-- Composer --}}
            @if ($conversation->blocked && $role === 'buyer')
                <p class="p-4 text-center text-sm text-muted border-t border-line-soft">The seller is not accepting messages in this chat.</p>
            @else
                <form method="POST" action="{{ route('messages.reply', $conversation) }}" class="p-3 border-t border-line-soft flex gap-2 items-end">
                    @csrf
                    <textarea name="body" rows="1" required maxlength="1500" placeholder="Write a message…" class="input !py-2.5 resize-none max-h-32" oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"
                              onkeydown="if(event.key==='Enter'&&!event.shiftKey&&window.innerWidth>1024){event.preventDefault();this.form.requestSubmit()}">{{ old('body') }}</textarea>
                    <button class="btn-primary !px-3.5 shrink-0" aria-label="Send">@svg('lucide-send', 'w-5 h-5')</button>
                </form>
            @endif
        </div>

        {{-- Side panel --}}
        <aside class="space-y-3">
            @if ($role === 'seller')
                <div class="card p-4">
                    <h2 class="font-extrabold text-sm flex items-center gap-1.5">@svg('lucide-users-round', 'w-4 h-4 text-brand') Lead tools</h2>
                    @if ($leadTools)
                        <form method="POST" action="{{ route('messages.update', $conversation) }}" class="mt-3 space-y-3">
                            @csrf @method('PATCH')
                            <div><label class="label">Status</label>
                                <select name="lead_status" class="input !py-2 !text-sm">@foreach (config('marketplace.lead_statuses') as $k => $label)<option value="{{ $k }}" @selected($conversation->lead_status === $k)>{{ $label }}</option>@endforeach</select></div>
                            <div><label class="label">Private note</label><textarea name="seller_note" rows="3" class="input !text-sm" placeholder="Only you can see this">{{ $conversation->seller_note }}</textarea></div>
                            <button class="btn-primary w-full !py-2">Save</button>
                        </form>
                    @else
                        <p class="mt-2 text-xs text-muted">Track this buyer’s status and keep private notes with the Premium package.</p>
                        <a href="{{ route('pricing') }}" class="btn-ghost w-full mt-2 !py-2 text-xs">See Premium</a>
                    @endif
                </div>
                <form method="POST" action="{{ route('messages.update', $conversation) }}" class="card p-4">
                    @csrf @method('PATCH')
                    <input type="hidden" name="block" value="{{ $conversation->blocked ? 0 : 1 }}">
                    <button class="w-full text-sm font-semibold {{ $conversation->blocked ? 'text-brand-dark' : 'text-red-600' }} flex items-center justify-center gap-1.5">@svg('lucide-ban', 'w-4 h-4') {{ $conversation->blocked ? 'Unblock this buyer' : 'Block this buyer' }}</button>
                </form>
            @else
                <div class="rounded-2xl bg-[#FFFBEB] border border-[#FDE68A] p-4 text-[13px] text-[#78350F]">
                    <p class="font-bold mb-1">Stay safe</p>
                    <p>Meet in a public place, inspect before paying, and never share OTP codes.</p>
                </div>
                <a href="{{ route('sellers.show', [$other, 'reviews' => 1]) }}" class="card p-4 block text-sm font-semibold hover:bg-mist">@svg('lucide-star', 'w-4 h-4 inline text-amber-500') Rate this seller</a>
            @endif
        </aside>
    </div>
</x-dashboard-shell>
