{{-- Original Sri Lanka skyline for hero backgrounds: dagoba, Sigiriya, nine-arch bridge, tea hills, palms, elephant. --}}
@php
    // Nine-arch bridge outline (deck from x=120 to x=440)
    $bx0 = 120; $bx1 = 440; $deck = 186; $base = 246; $n = 9; $w = ($bx1 - $bx0) / $n;
    $bridge = "M{$bx0} {$base} L{$bx0} {$deck} L{$bx1} {$deck} L{$bx1} {$base}";
    for ($i = $n - 1; $i >= 0; $i--) {
        $a = round($bx0 + $i * $w + 5, 1); $b = round($bx0 + ($i + 1) * $w - 5, 1); $r = round(($b - $a) / 2, 1);
        $bridge .= " L{$b} {$base} L{$b} ".($deck + 22)." A{$r} {$r} 0 0 0 {$a} ".($deck + 22)." L{$a} {$base}";
    }
    $bridge .= ' Z';
@endphp
<div {{ $attributes->merge(['class' => 'pointer-events-none select-none']) }} aria-hidden="true">
    {{-- faint lotus pattern --}}
    <svg class="absolute inset-0 w-full h-full opacity-[.05]" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <pattern id="lotus-tile" width="88" height="88" patternUnits="userSpaceOnUse">
                <g fill="none" stroke="#fff" stroke-width="1.3">
                    <path d="M44 20c-7 9-7 19 0 26 7-7 7-17 0-26z"/>
                    <path d="M44 46c-4-9-13-14-22-12 2 9 11 14 22 12z"/>
                    <path d="M44 46c4-9 13-14 22-12-2 9-11 14-22 12z"/>
                    <path d="M30 52h28"/>
                    <circle cx="0" cy="0" r="3"/><circle cx="88" cy="0" r="3"/><circle cx="0" cy="88" r="3"/><circle cx="88" cy="88" r="3"/>
                </g>
            </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#lotus-tile)"/>
    </svg>

    {{-- skyline --}}
    <div class="absolute bottom-0 inset-x-0 h-[34px] sm:h-[50px] bg-[#1E0F45]"></div>
    <svg class="absolute bottom-[33px] sm:bottom-[49px] left-0 w-full h-[150px] sm:h-[210px] lg:h-[240px]" viewBox="0 0 1440 260" preserveAspectRatio="xMidYMax slice" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <radialGradient id="sun-glow" cx="50%" cy="50%" r="50%">
                <stop offset="0" stop-color="#FDE68A" stop-opacity=".35"/>
                <stop offset="1" stop-color="#FDE68A" stop-opacity="0"/>
            </radialGradient>
            <linearGradient id="sun-disc" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#FDE68A"/><stop offset="1" stop-color="#F59E0B"/>
            </linearGradient>
            <linearGradient id="dagoba" x1="0" y1="0" x2="1" y2="0">
                <stop offset="0" stop-color="#FFFFFF"/><stop offset="1" stop-color="#DDD6FE"/>
            </linearGradient>
            <g id="palm">
                <path d="M0 0 C 4 -30, 2 -60, 10 -88" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
                <g fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round">
                    <path d="M10 -88 Q -14 -98 -34 -80"/><path d="M10 -88 Q -8 -110 -28 -108"/>
                    <path d="M10 -88 Q 16 -116 34 -114"/><path d="M10 -88 Q 36 -98 52 -78"/>
                    <path d="M10 -88 Q 30 -86 40 -64"/><path d="M10 -88 Q -10 -84 -18 -62"/>
                </g>
                <circle cx="9" cy="-84" r="4" fill="currentColor"/>
            </g>
        </defs>

        {{-- sun rising behind Sigiriya --}}
        <circle cx="930" cy="104" r="96" fill="#FDE68A" opacity=".06"/><circle cx="930" cy="104" r="68" fill="#FDE68A" opacity=".1"/>
        <circle cx="930" cy="104" r="44" fill="url(#sun-disc)" opacity=".95"/>

        {{-- birds --}}
        <g fill="none" stroke="#fff" stroke-opacity=".55" stroke-width="2" stroke-linecap="round" class="lk-birds">
            <path d="M700 60 q6 -6 12 0 q6 -6 12 0"/><path d="M738 44 q5 -5 10 0 q5 -5 10 0"/><path d="M672 36 q4 -4 8 0 q4 -4 8 0"/>
            <path d="M1180 70 q5 -5 10 0 q5 -5 10 0"/>
        </g>

        {{-- far mountains (Adam's Peak range) --}}
        <path d="M0 200 L90 160 L170 176 L290 96 L330 118 L420 150 L520 170 L640 150 L760 176 L1100 170 L1210 128 L1300 150 L1440 120 L1440 260 L0 260Z" fill="#5B21B6" opacity=".45"/>

        {{-- Sigiriya rock --}}
        <path d="M792 260 L820 188 C 828 150, 842 122, 872 114 L 958 110 C 990 112, 1004 140, 1010 176 L 1040 260 Z" fill="#3B1585"/>
        <path d="M872 114 L 958 110 C 975 111, 986 118, 993 130 L 866 132 C 866 124, 868 118, 872 114Z" fill="#4C1D95"/>

        {{-- tea-estate hills with rows --}}
        <path d="M0 230 C 120 196, 240 206, 360 214 C 520 224, 620 208, 760 214 C 900 222, 1000 200, 1120 196 C 1240 192, 1340 206, 1440 196 L1440 260 L0 260Z" fill="#2E1065"/>
        <g fill="none" stroke="#A78BFA" stroke-opacity=".22" stroke-width="2" stroke-dasharray="2 7" stroke-linecap="round">
            <path d="M1060 214 C 1160 204, 1280 208, 1440 202"/><path d="M1040 226 C 1150 216, 1290 220, 1440 214"/>
            <path d="M1030 238 C 1150 230, 1300 232, 1440 226"/><path d="M0 240 C 100 222, 200 226, 300 230"/>
        </g>

        {{-- nine-arch bridge with a train --}}
        <path d="{{ $bridge }}" fill="#3B1585"/>
        <rect x="{{ $bx0 - 6 }}" y="{{ $deck - 4 }}" width="{{ $bx1 - $bx0 + 12 }}" height="5" rx="2" fill="#4C1D95"/>
        <g class="lk-train">
            <rect x="196" y="{{ $deck - 20 }}" width="34" height="16" rx="4" fill="#1E0F45"/>
            <rect x="233" y="{{ $deck - 20 }}" width="34" height="16" rx="3" fill="#1E0F45"/>
            <rect x="270" y="{{ $deck - 20 }}" width="34" height="16" rx="3" fill="#1E0F45"/>
            <path d="M307 {{ $deck - 4 }} L307 {{ $deck - 20 }} L330 {{ $deck - 20 }} Q342 {{ $deck - 20 }} 342 {{ $deck - 8 }} L342 {{ $deck - 4 }}Z" fill="#1E0F45"/>
            @foreach ([202, 214, 239, 251, 276, 288, 313] as $wx)
                <rect x="{{ $wx }}" y="{{ $deck - 16 }}" width="7" height="5" rx="1" fill="#FCD34D"/>
            @endforeach
            <rect x="196" y="{{ $deck - 9 }}" width="146" height="2" fill="#E11D48" opacity=".9"/>
        </g>

        {{-- foreground ground --}}
        <path d="M0 246 C 200 238, 360 244, 520 240 C 700 236, 860 246, 1040 242 C 1220 238, 1340 244, 1440 240 L1440 260 L0 260Z" fill="#1E0F45"/>

        {{-- dagoba (stupa) --}}
        <g>
            <rect x="494" y="228" width="132" height="14" rx="2" fill="#C4B5FD"/>
            <rect x="506" y="218" width="108" height="11" rx="2" fill="#DDD6FE"/>
            <path d="M514 219 C 514 172, 534 150, 560 148 C 586 150, 606 172, 606 219 Z" fill="url(#dagoba)"/>
            <rect x="549" y="134" width="22" height="15" rx="1" fill="#EDE9FE"/>
            <path d="M552 134 L560 78 L568 134 Z" fill="#FCD34D"/>
            <g stroke="#F59E0B" stroke-width="1.5"><path d="M554 124h12"/><path d="M555 114h10"/><path d="M556 104h8"/></g>
            <circle cx="560" cy="76" r="3.5" fill="#FDE68A"/>
            <path d="M494 242 h132" stroke="#A78BFA" stroke-width="2"/>
        </g>

        {{-- elephant --}}
        <g fill="#160A33" transform="translate(712 206)">
            <ellipse cx="0" cy="0" rx="40" ry="26"/>
            <rect x="-32" y="8" width="13" height="30" rx="4"/><rect x="-14" y="10" width="12" height="28" rx="4"/>
            <rect x="12" y="10" width="12" height="28" rx="4"/><rect x="26" y="8" width="13" height="30" rx="4"/>
            <circle cx="40" cy="-10" r="20"/>
            <path d="M54 -2 C 66 12, 64 30, 56 38 L 49 37 C 56 26, 54 14, 44 6 Z"/>
            <ellipse cx="30" cy="-6" rx="11" ry="16" fill="#241048"/>
            <path d="M-40 -4 C -50 4, -50 14, -46 20" fill="none" stroke="#160A33" stroke-width="3" stroke-linecap="round"/>
            <path d="M52 8 l10 4" stroke="#F5F3FF" stroke-width="3" stroke-linecap="round"/>
        </g>
        <g fill="#160A33" transform="translate(780 222) scale(.55)">
            <ellipse cx="0" cy="0" rx="40" ry="26"/>
            <rect x="-32" y="8" width="13" height="30" rx="4"/><rect x="-14" y="10" width="12" height="28" rx="4"/>
            <rect x="12" y="10" width="12" height="28" rx="4"/><rect x="26" y="8" width="13" height="30" rx="4"/>
            <circle cx="40" cy="-10" r="20"/>
            <path d="M54 -2 C 66 12, 64 30, 56 38 L 49 37 C 56 26, 54 14, 44 6 Z"/>
        </g>

        {{-- coconut palms --}}
        <g class="text-[#160A33]">
            <use href="#palm" transform="translate(52 248) scale(1.15)"/>
            <use href="#palm" transform="translate(84 250) scale(.8) rotate(6)"/>
            <use href="#palm" transform="translate(452 246) scale(.95) rotate(-4)"/>
            <use href="#palm" transform="translate(1078 246) scale(1)"/>
            <use href="#palm" transform="translate(1296 248) scale(1.25) rotate(-6)"/>
            <use href="#palm" transform="translate(1340 250) scale(.85) rotate(8)"/>
            <use href="#palm" transform="translate(1406 248) scale(1.05)"/>
        </g>
    </svg>
</div>
