<x-layouts.app title="{{ __('Page not found') }}">
<div class="max-w-xl mx-auto px-4 py-20 text-center">
    <p class="font-display text-6xl font-extrabold text-brand">404</p>
    <p class="mt-3 text-xl font-extrabold">{{ __('We could not find that page') }}</p>
    <p class="text-sm text-muted mt-2">{{ __('The link may be broken or the ad may have been removed.') }}</p>
    <a href="{{ route('home') }}" class="btn-primary mt-5">{{ __('Back to home') }}</a>
</div>
</x-layouts.app>
