<x-dashboard-shell title="Saved ads" active="saved">
    <h1 class="text-2xl font-extrabold">{{ __('Saved ads') }}</h1>
    <div class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
        @forelse ($listings as $ad)
            <x-ad-tile :listing="$ad" />
        @empty
            <div class="col-span-full card p-10 text-center">
                <span class="mx-auto w-14 h-14 rounded-full bg-lilac text-brand flex items-center justify-center">@svg('lucide-heart', 'w-6 h-6')</span>
                <p class="mt-3 font-extrabold text-lg">{{ __('No saved ads yet') }}</p>
                <p class="text-sm text-muted mt-1">{{ __('Tap the heart on any ad to keep it here.') }}</p>
                <a href="{{ route('listings.index') }}" class="btn-primary mt-4">{{ __('Browse ads') }}</a>
            </div>
        @endforelse
    </div>
    <div class="mt-5">{{ $listings->links() }}</div>
</x-dashboard-shell>
