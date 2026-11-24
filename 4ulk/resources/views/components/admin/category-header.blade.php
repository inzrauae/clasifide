@props(['category', 'tab'])
@php
    $tabs = [
        'show' => ['Overview', 'lucide-chart-column'],
        'ads' => ['Ads', 'lucide-tag'],
        'edit' => ['Settings & fields', 'lucide-settings-2'],
    ];
    $all = \App\Models\Category::whereNull('parent_id')->orderBy('sort')->get(['id', 'name', 'slug']);
@endphp
<div class="mb-4 -mt-1">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <span class="w-11 h-11 rounded-xl bg-lilac text-brand flex items-center justify-center shrink-0">@svg('lucide-'.$category->icon, 'w-5 h-5')</span>
            <div class="min-w-0">
                <a href="{{ route('admin.categories') }}" class="text-xs font-semibold text-muted hover:text-brand-dark">← All categories</a>
                <p class="text-sm text-muted truncate">
                    {{ $category->hint }}
                    @if ($category->isPremium())· <span class="font-semibold text-[#78350F]">Premium: Rs {{ number_format($category->posting_fee) }}/ad</span>@endif
                    @unless ($category->is_active)· <span class="font-semibold text-red-600">Hidden</span>@endunless
                </p>
            </div>
        </div>
        <form x-data class="shrink-0">
            <label class="sr-only" for="cat-switch">Switch category</label>
            <select id="cat-switch" class="input !py-1.5 !text-sm !w-auto" @change="window.location = $event.target.value">
                @foreach ($all as $c)
                    <option value="{{ route('admin.categories.'.$tab, $c) }}" @selected($c->id === $category->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="mt-3 flex gap-1 overflow-x-auto no-scrollbar border-b border-line">
        @foreach ($tabs as $k => [$label, $icon])
            <a href="{{ route('admin.categories.'.$k, $category) }}" class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-bold border-b-2 -mb-px {{ $tab === $k ? 'border-brand text-brand-dark' : 'border-transparent text-muted hover:text-ink' }}">@svg($icon, 'w-4 h-4') {{ $label }}</a>
        @endforeach
    </div>
</div>
