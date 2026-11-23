@props(['title', 'subtitle' => null])
<x-layouts.app :title="$title">
<div class="hero-band">
    <div class="max-w-md mx-auto px-4 py-8 sm:py-14">
        <div class="card p-5 sm:p-7 shadow-2xl">
            <h1 class="text-2xl font-extrabold">{{ $title }}</h1>
            @if ($subtitle)<p class="text-sm text-muted mt-1">{{ $subtitle }}</p>@endif
            <div class="mt-5">{{ $slot }}</div>
        </div>
    </div>
</div>
</x-layouts.app>
