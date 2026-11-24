<x-admin-shell :title="$category->name" active="categories">
    <x-admin.category-header :category="$category" tab="ads" />
    <x-admin-tabs route="admin.categories.ads" :params="['category' => $category]" :current="$status" :tabs="\App\Http\Controllers\Admin\ListingController::TABS" />

    <form class="mb-4 flex flex-wrap gap-2 items-center">
        <input type="hidden" name="status" value="{{ $status }}">
        <input name="q" value="{{ request('q') }}" placeholder="Search title, phone or ad #" class="input !py-2 !w-auto min-w-0 flex-1 sm:flex-none sm:w-64">
        @foreach ($category->selectFields() as $f)
            <label class="sr-only" for="spec-{{ $loop->index }}">{{ $f['name'] }}</label>
            <select id="spec-{{ $loop->index }}" name="spec[{{ $f['name'] }}]" class="input !py-2 !w-auto !text-sm">
                <option value="">{{ $f['name'] }}: any</option>
                @foreach ($f['options'] as $o)<option value="{{ $o }}" @selected(($filters[$f['name']] ?? null) === $o)>{{ $o }}</option>@endforeach
            </select>
        @endforeach
        <button class="btn-ghost !py-2">@svg('lucide-list-filter', 'w-4 h-4') Filter</button>
        @if ($filters || request('q'))<a href="{{ route('admin.categories.ads', [$category, 'status' => $status]) }}" class="text-sm font-semibold text-muted hover:text-ink">Clear</a>@endif
    </form>

    <div class="space-y-3">
        @forelse ($listings as $ad)
            <x-admin.ad-card :ad="$ad" show-specs />
        @empty
            <p class="card p-10 text-center text-muted">No {{ $category->name }} ads here.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $listings->links() }}</div>
</x-admin-shell>
