<x-layouts.app title="Not allowed">
<div class="max-w-xl mx-auto px-4 py-20 text-center">
    <p class="font-display text-6xl font-extrabold text-brand">403</p>
    <p class="mt-3 text-xl font-extrabold">You do not have access to this page</p>
    <p class="text-sm text-muted mt-2">{{ $exception->getMessage() ?: 'Sign in with the right account and try again.' }}</p>
    <a href="{{ route('home') }}" class="btn-primary mt-5">Back to home</a>
</div>
</x-layouts.app>
