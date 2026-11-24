@php($me = auth()->user())
<x-dashboard-shell title="{{ __('Messages') }}" active="messages">
    <h1 class="text-2xl font-extrabold">{{ __('Messages') }}</h1>
    <div class="mt-4 flex gap-1 overflow-x-auto no-scrollbar border-b border-line">
        @foreach (['all' => 'All', 'selling' => 'Selling', 'buying' => 'Buying', 'archived' => 'Archived'] as $k => $label)
            <a href="{{ route('messages.index', ['box' => $k]) }}" class="shrink-0 px-4 py-2.5 text-sm font-bold border-b-2 -mb-px {{ $box === $k ? 'border-brand text-brand-dark' : 'border-transparent text-muted hover:text-ink' }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="mt-4 card divide-y divide-line-soft">
        @forelse ($conversations as $c)
            @php($other = $c->otherParty($me))
            @php($unread = $c->unreadFor($me))
            <a href="{{ route('messages.show', $c) }}" class="flex items-center gap-3 p-3 sm:p-4 hover:bg-[#FAF9FF] {{ $unread ? 'bg-[#FAF9FF]' : '' }}">
                <div class="relative w-14 h-14 rounded-xl overflow-hidden shrink-0 bg-mist">@if ($c->listing)<x-ad-image :listing="$c->listing" />@endif</div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-bold truncate {{ $unread ? 'text-ink' : '' }}">{{ $other?->name }}</p>
                        <span class="text-[11px] text-faint shrink-0">{{ $c->last_message_at?->diffForHumans(short: true) }}</span>
                    </div>
                    <p class="text-xs text-muted truncate">{{ $c->roleOf($me) === 'seller' ? __('Buyer') : __('Seller') }} · {{ $c->listing?->title ?? __('Ad removed') }}</p>
                    <p class="text-sm truncate {{ $unread ? 'font-bold text-ink' : 'text-muted' }}">{{ $c->latestMessage?->sender_id === $me->id ? 'You: ' : '' }}{{ $c->latestMessage?->body }}</p>
                </div>
                @if ($unread)<span class="min-w-5 h-5 px-1.5 rounded-full bg-brand text-white text-[11px] font-bold flex items-center justify-center">{{ $unread }}</span>@endif
            </a>
        @empty
            <div class="p-10 text-center">
                <span class="mx-auto w-14 h-14 rounded-full bg-lilac text-brand flex items-center justify-center">@svg('lucide-message-square', 'w-6 h-6')</span>
                <p class="mt-3 font-extrabold">{{ __('No conversations yet') }}</p>
                <p class="text-sm text-muted mt-1">{{ __('Chats with buyers and sellers will appear here.') }}</p>
            </div>
        @endforelse
    </div>
    <div class="mt-5">{{ $conversations->links() }}</div>
</x-dashboard-shell>
