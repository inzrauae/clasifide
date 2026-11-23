@props(['title', 'lead' => null])
<x-layouts.app :title="$title">
<section class="hero-band text-white">
    <div class="max-w-3xl mx-auto px-4 py-10 sm:py-14">
        <h1 class="font-display text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $title }}</h1>
        @if ($lead)<p class="mt-2 text-white/75">{{ $lead }}</p>@endif
    </div>
</section>
<article class="max-w-3xl mx-auto px-4 py-8 text-[15px] leading-relaxed text-[#334155] space-y-4 [&_h2]:text-ink [&_h2]:text-lg [&_h2]:font-extrabold [&_h2]:mt-6 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-1">
    {{ $slot }}
</article>
</x-layouts.app>
