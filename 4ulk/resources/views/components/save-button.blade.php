@props(['listing', 'saved' => false])
<button type="button"
        x-data="favorite('{{ route('listings.favorite', $listing) }}', {{ $saved ? 'true' : 'false' }}, {{ auth()->check() ? 'true' : 'false' }}, '{{ route('login') }}')"
        @click.prevent.stop="toggle()" :aria-pressed="saved" aria-label="{{ __('Save ad') }}"
        {{ $attributes->merge(['class' => 'tap hit w-8 h-8 rounded-full bg-white/95 shadow flex items-center justify-center']) }}>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" :class="saved ? 'fill-[#E11D48] text-[#E11D48]' : 'fill-none text-muted'" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"/></svg>
</button>
