@props(['listing'])
<a href="{{ $listing->url() }}" class="tap group bg-white rounded-xl border border-line overflow-hidden flex flex-col hover:shadow-[0_10px_28px_-14px_rgba(76,29,149,.4)] transition-shadow">
    <div class="relative aspect-[4/3] overflow-hidden">
        <x-ad-image :listing="$listing" class="transition-transform duration-500 group-hover:scale-105" />
        <div class="absolute top-2 left-2 flex gap-1">
            @if ($listing->isTop())<span class="badge bg-sun text-ink">TOP AD</span>@endif
            @if ($listing->isUrgent())<span class="badge bg-red-600 text-white">URGENT</span>@endif
        </div>
        <x-save-button :listing="$listing" :saved="\App\Support\Saved::has($listing->id)" class="absolute top-2 right-2" />
    </div>
    <div class="p-2.5 sm:p-3 flex-1 flex flex-col">
        <h3 class="text-[13px] sm:text-[14px] font-semibold text-ink leading-snug line-clamp-2">{{ $listing->title }}</h3>
        <p class="mt-1 text-[11.5px] text-muted truncate">{{ $listing->place() }}</p>
        <div class="mt-auto pt-1.5 flex flex-wrap items-end justify-between gap-x-1 gap-y-1">
            <p class="min-w-0 text-[14px] sm:text-[15px] font-extrabold text-brand-dark">{{ $listing->priceLabel() }}</p>
            @if ($listing->user?->activeSubscription)<span class="shrink-0"><x-member-badge /></span>@endif
        </div>
    </div>
</a>
