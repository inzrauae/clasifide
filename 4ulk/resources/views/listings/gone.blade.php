<x-layouts.app :title="__('Ad not available')">
<div class="max-w-xl mx-auto px-4 py-20 text-center">
    <span class="mx-auto w-16 h-16 rounded-full bg-lilac text-brand flex items-center justify-center">@svg('lucide-tag', 'w-7 h-7')</span>
    <p class="mt-4 text-xl font-extrabold">{{ __('This ad is no longer available') }}</p>
    <p class="text-sm text-muted mt-2">{{ __('It may have been sold, expired or removed by the seller.') }}</p>
    <a href="{{ route('listings.index') }}" class="btn-primary mt-5">{{ __('Browse all ads') }}</a>
</div>
</x-layouts.app>
