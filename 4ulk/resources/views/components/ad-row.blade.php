@props(['listing'])
@php($top = $listing->isTop())
<a href="{{ $listing->url() }}" class="tap group relative flex gap-3 sm:gap-4 rounded-xl border p-2.5 sm:p-3 transition-shadow hover:shadow-[0_8px_24px_-12px_rgba(76,29,149,.35)] {{ $top ? 'bg-[#FFFBEB] border-[#FDE68A]' : 'bg-white border-line' }}">
    <div class="relative w-[112px] h-[96px] sm:w-[168px] sm:h-[124px] shrink-0 rounded-lg overflow-hidden">
        <x-ad-image :listing="$listing" class="transition-transform duration-500 group-hover:scale-105" />
        @if ($top)<span class="absolute top-1.5 left-1.5 badge bg-sun text-ink">TOP AD</span>@endif
        @if ($listing->images->count() > 1)
            <span class="absolute bottom-1.5 left-1.5 inline-flex items-center gap-1 text-[10px] font-semibold text-white bg-black/55 px-1.5 py-0.5 rounded">@svg('lucide-camera', 'w-3 h-3') {{ $listing->images->count() }}</span>
        @endif
    </div>
    <div class="min-w-0 flex-1 flex flex-col py-0.5 pr-7">
        <h3 class="text-[14px] sm:text-[16px] font-semibold text-ink leading-snug line-clamp-2 group-hover:text-brand-dark">{{ $listing->title }}</h3>
        <p class="mt-1 text-[12px] sm:text-[13px] text-muted truncate">{{ $listing->place() }}, {{ $listing->category->label() }}</p>
        @if ($listing->condition)<p class="text-[12px] text-muted">{{ $listing->condition }}</p>@endif
        <div class="mt-auto pt-1.5 flex flex-wrap items-end justify-between gap-x-2 gap-y-1">
            <span class="text-[14px] sm:text-[16px] font-extrabold text-brand-dark">{{ $listing->priceLabel() }}</span>
            <span class="flex gap-1">
                @if ($listing->isUrgent())<span class="badge bg-red-600 text-white">URGENT</span>@endif
                @if ($listing->user?->activeSubscription)<x-member-badge />@endif
                @if ($listing->user?->isVerified())<span class="badge bg-emerald-100 text-emerald-700">@svg('lucide-shield-check', 'w-3 h-3') VERIFIED</span>@endif
            </span>
        </div>
    </div>
    <span class="absolute right-3 top-12 hidden sm:block text-[11px] text-faint">{{ ($listing->bumped_at ?? $listing->published_at ?? $listing->created_at)->diffForHumans(short: true) }}</span>
    <x-save-button :listing="$listing" :saved="\App\Support\Saved::has($listing->id)" class="absolute top-2.5 right-2.5 !w-7 !h-7 !shadow-none border border-line" />
</a>
