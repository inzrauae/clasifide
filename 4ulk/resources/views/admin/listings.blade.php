<x-admin-shell title="Ads & moderation" active="listings">
    <x-admin-tabs route="admin.listings" :current="$status" :tabs="\App\Http\Controllers\Admin\ListingController::TABS" />
    <form class="mb-4 flex gap-2"><input type="hidden" name="status" value="{{ $status }}"><input name="q" value="{{ request('q') }}" placeholder="Search title, phone or ad #" class="input !py-2 max-w-sm"><button class="btn-ghost !py-2">Search</button></form>

    <div class="space-y-3">
        @forelse ($listings as $ad)
            <x-admin.ad-card :ad="$ad" />
        @empty
            <p class="card p-10 text-center text-muted">Nothing here.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $listings->links() }}</div>
</x-admin-shell>
