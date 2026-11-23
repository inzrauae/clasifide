@props(['listing', 'src' => null])
@php($src = $src ?? $listing->coverUrl())
@if ($src)
    <img src="{{ $src }}" alt="{{ $listing->title }}" loading="lazy" {{ $attributes->merge(['class' => 'absolute inset-0 w-full h-full object-cover']) }}
         onerror="this.replaceWith(Object.assign(document.createElement('div'),{className:'absolute inset-0 bg-gradient-to-br from-[#F3EEFF] via-[#EDE7FF] to-[#E4DAFF]'}))">
@else
    <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-[#F3EEFF] via-[#EDE7FF] to-[#E4DAFF]">
        @svg('lucide-'.($listing->category->icon ?? 'tag'), 'w-[30%] h-[30%] max-w-16 max-h-16 text-brand/60', ['stroke-width' => '1.6'])
    </div>
@endif
