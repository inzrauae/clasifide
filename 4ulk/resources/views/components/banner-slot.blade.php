@props(['banner' => null, 'placement' => 'home_top'])
@if ($banner)
    <a href="{{ route('banners.click', $banner) }}" target="_blank" rel="noopener sponsored" {{ $attributes->merge(['class' => 'block relative rounded-2xl overflow-hidden border border-line bg-white']) }}>
        <img src="{{ asset($banner->image) }}" alt="{{ $banner->advertiser }}" class="w-full h-auto block" loading="lazy">
        <span class="absolute top-2 right-2 text-[10px] font-bold uppercase tracking-wider bg-black/50 text-white px-1.5 py-0.5 rounded">Ad</span>
    </a>
@else
    <a href="{{ route('advertise') }}" {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3 rounded-2xl border-2 border-dashed border-[#C4B5FD] bg-white/60 px-4 py-3 text-sm hover:bg-mist']) }}>
        <span class="flex items-center gap-2 text-[#4C1D95] font-semibold">@svg('lucide-megaphone', 'w-4 h-4') Advertise your business here</span>
        <span class="text-brand-dark font-bold whitespace-nowrap">From Rs {{ number_format(config("marketplace.banner_placements.$placement.price", 4500)) }}/week</span>
    </a>
@endif
