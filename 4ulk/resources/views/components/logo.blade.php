@props(['light' => false])
@php($ink = $light ? '#FFFFFF' : '#1E1B4B')
@php($accent = $light ? '#FCD34D' : '#7C3AED')
@php($gid = 'pin-'.($light ? 'l' : 'd'))
<svg viewBox="0 0 154 56" {{ $attributes->merge(['class' => 'h-9 w-auto']) }} role="img" aria-label="4U.lk">
    <defs>
        <linearGradient id="{{ $gid }}" x1="6" y1="2" x2="44" y2="54" gradientUnits="userSpaceOnUse">
            <stop stop-color="#A855F7" /><stop offset=".55" stop-color="#7C3AED" /><stop offset="1" stop-color="#4C1D95" />
        </linearGradient>
    </defs>
    <path d="M24 2C12.4 2 3 11.2 3 22.6c0 14.8 17.6 29.6 19.6 31.2a2.2 2.2 0 0 0 2.8 0C27.4 52.2 45 37.4 45 22.6 45 11.2 35.6 2 24 2z" fill="url(#{{ $gid }})" />
    <g stroke="#fff" stroke-width="4.2" stroke-linecap="round" stroke-linejoin="round" fill="none">
        <path d="M19 12.5V31M19 12.5L10 26.5H22.5" /><path d="M27 12.5V23.5a5 5 0 0 0 10 0V12.5" />
    </g>
    <circle cx="41.5" cy="6.5" r="4" fill="#FCD34D" stroke="{{ $light ? '#0E0820' : '#fff' }}" stroke-width="1.6" />
    <g stroke="{{ $ink }}" stroke-width="6.4" stroke-linecap="round" stroke-linejoin="round" fill="none">
        <path d="M76 15V40M76 15L62 32.5H81.5" /><path d="M89.5 15V31a8.75 8.75 0 0 0 17.5 0V15" />
    </g>
    <circle cx="116.5" cy="37.4" r="3.6" fill="{{ $accent }}" />
    <g stroke="{{ $accent }}" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" fill="none">
        <path d="M125.5 12.5V40" /><path d="M134.5 12.5V40M146 23.5L135 32M139.5 29L147 40" />
    </g>
</svg>
