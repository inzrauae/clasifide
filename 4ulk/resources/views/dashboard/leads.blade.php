@php($statuses = config('marketplace.lead_statuses'))
@php($colors = ['new' => 'bg-[#EDE9FE] text-brand-dark', 'contacted' => 'bg-sky-100 text-sky-700', 'negotiating' => 'bg-amber-100 text-amber-800', 'won' => 'bg-emerald-100 text-emerald-700', 'lost' => 'bg-slate-100 text-slate-600'])
<x-dashboard-shell title="Buyer inquiries" active="leads">
    <h1 class="text-2xl font-extrabold">{{ __('Buyer inquiries') }}</h1>
    <p class="text-sm text-muted">{{ __('Every buyer who chatted with you, so no lead slips through.') }}</p>

    <div class="mt-4 grid grid-cols-3 sm:grid-cols-6 gap-2">
        <a href="{{ route('dashboard.leads') }}" class="card p-3 text-center {{ ! $status ? 'ring-2 ring-brand' : '' }}"><p class="text-xl font-extrabold">{{ $pipeline->sum() }}</p><p class="text-xs text-muted">{{ __('All') }}</p></a>
        @foreach ($statuses as $k => $label)
            <a href="{{ route('dashboard.leads', ['status' => $k]) }}" class="card p-3 text-center {{ $status === $k ? 'ring-2 ring-brand' : '' }}"><p class="text-xl font-extrabold">{{ $pipeline[$k] ?? 0 }}</p><p class="text-xs text-muted">{{ __($label) }}</p></a>
        @endforeach
    </div>

    @unless ($leadTools)
        <div class="mt-4 rounded-2xl bg-night text-white p-4 flex flex-col sm:flex-row gap-3 sm:items-center">
            <p class="flex-1 text-sm">{{ __('Lead tools let you set a status and private notes on every inquiry. Included in the Premium package.') }}</p>
            <a href="{{ route('pricing') }}" class="btn-sun !py-2">{{ __('Upgrade') }}</a>
        </div>
    @endunless

    <div class="mt-4 card divide-y divide-line-soft">
        @forelse ($leads as $c)
            <div class="p-3 sm:p-4 flex flex-col sm:flex-row gap-3 sm:items-center">
                <a href="{{ route('messages.show', $c) }}" class="flex items-center gap-3 min-w-0 flex-1">
                    <span class="w-10 h-10 rounded-full brand-gradient text-white text-sm font-bold flex items-center justify-center shrink-0">{{ $c->buyer->initials() }}</span>
                    <span class="min-w-0">
                        <span class="block font-bold truncate">{{ $c->buyer->name }} @if ($c->seller_unread)<span class="badge bg-sun text-ink ml-1">{{ __(':count new', ['count' => $c->seller_unread]) }}</span>@endif</span>
                        <span class="block text-xs text-muted truncate">{{ $c->listing?->title ?? __('Ad removed') }}</span>
                        <span class="block text-xs text-faint truncate">“{{ \Illuminate\Support\Str::limit($c->latestMessage?->body, 80) }}” · {{ $c->last_message_at?->diffForHumans() }}</span>
                        @if ($leadTools && $c->seller_note)<span class="block text-xs text-[#4C1D95] mt-0.5">📝 {{ \Illuminate\Support\Str::limit($c->seller_note, 80) }}</span>@endif
                    </span>
                </a>
                @if ($leadTools)
                    <form method="POST" action="{{ route('messages.update', $c) }}" class="shrink-0">
                        @csrf @method('PATCH')
                        <select name="lead_status" onchange="this.form.submit()" class="input !py-1.5 !text-sm !w-40" aria-label="{{ __('Lead status') }}">
                            @foreach ($statuses as $k => $label)<option value="{{ $k }}" @selected($c->lead_status === $k)>{{ __($label) }}</option>@endforeach
                        </select>
                    </form>
                @else
                    <span class="badge {{ $colors[$c->lead_status] ?? '' }} self-start sm:self-center">{{ mb_strtoupper(__($statuses[$c->lead_status] ?? $c->lead_status)) }}</span>
                @endif
            </div>
        @empty
            <p class="p-10 text-center text-sm text-muted">{{ __('No buyer inquiries yet. Promote an ad to get more buyers.') }}</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $leads->links() }}</div>
</x-dashboard-shell>
