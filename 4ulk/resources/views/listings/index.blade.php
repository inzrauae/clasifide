@php
    $heading = __(':what in :place', ['what' => $category?->name ?? ($q !== '' ? '“'.$q.'”' : __('All ads')), 'place' => $loc ? __($loc) : __('Sri Lanka')]);
    $params = request()->except('page');
    $with = fn (array $patch) => route('listings.index', array_filter(array_merge($params, $patch), fn ($v) => $v !== null && $v !== ''));
    $sorts = ['' => __('Top ads first'), 'date' => __('Newest'), 'price_asc' => __('Price: low to high'), 'price_desc' => __('Price: high to low')];
    $chips = array_filter([
        $category ? [$category->name, ['cat' => null]] : null,
        $loc ? [__($loc), ['loc' => null, 'city' => null]] : null,
        request('city') ? [request('city'), ['city' => null]] : null,
        $q !== '' ? ['“'.$q.'”', ['q' => null]] : null,
        request('min') || request('max') ? ['Rs '.number_format((int) request('min')).' – '.(request('max') ? number_format((int) request('max')) : __('any')), ['min' => null, 'max' => null]] : null,
        request('condition') ? [__(request('condition')), ['condition' => null]] : null,
        request('verified') ? [__('Verified sellers'), ['verified' => null]] : null,
        request('member') ? [__('Members only'), ['member' => null]] : null,
        request('urgent') ? [__('Urgent'), ['urgent' => null]] : null,
    ]);
@endphp
<x-layouts.app :title="$heading" :description="__('Find :what on 4U.lk. :count ads.', ['what' => $heading, 'count' => $listings->total()])">
<div class="bg-white border-b border-line">
    <div class="max-w-6xl mx-auto px-4 py-3 sm:py-4">
        <x-search-bar :loc="$loc" :q="$q" :cat="$category?->slug" :counts="$districtCounts->all()" />
    </div>
</div>

<div class="max-w-6xl mx-auto px-4 py-4 sm:py-6" x-data="{ filters: false }">
    <div class="grid grid-cols-[minmax(0,1fr)] lg:grid-cols-[270px_1fr] gap-6 items-start">

        {{-- Filters --}}
        <aside :class="filters ? 'fixed inset-0 z-[70] bg-white overflow-y-auto p-4 pb-28' : 'hidden'" class="lg:block lg:static lg:p-0 lg:bg-transparent lg:overflow-visible">
            <div class="flex items-center justify-between lg:hidden mb-3">
                <h2 class="text-lg font-extrabold">{{ __('Filters') }}</h2>
                <button @click="filters = false" class="p-2 rounded-full hover:bg-mist" aria-label="{{ __('Close') }}">@svg('lucide-x', 'w-5 h-5')</button>
            </div>
            <div class="space-y-4">
                <div class="card p-4">
                    <h3 class="text-sm font-extrabold mb-2">{{ __('Category') }}</h3>
                    <ul class="text-[13.5px] space-y-0.5">
                        <li><a href="{{ $with(['cat' => null]) }}" class="flex justify-between px-2 py-1.5 rounded-lg {{ ! $category ? 'bg-[#EDE9FE] text-brand-dark font-bold' : 'hover:bg-mist' }}">{{ __('All categories') }} <span class="text-faint text-xs">{{ $catCounts->sum() }}</span></a></li>
                        @foreach ($categories as $c)
                            <li><a href="{{ $with(['cat' => $c->slug]) }}" class="flex justify-between gap-2 px-2 py-1.5 rounded-lg {{ $category?->id === $c->id ? 'bg-[#EDE9FE] text-brand-dark font-bold' : 'hover:bg-mist' }}">
                                <span class="flex items-center gap-2 min-w-0">@svg('lucide-'.$c->icon, 'w-4 h-4 text-brand shrink-0') <span class="truncate">{{ $c->name }}</span></span>
                                <span class="text-faint text-xs">{{ $catCounts[$c->id] ?? 0 }}</span></a></li>
                        @endforeach
                    </ul>
                </div>

                @if ($loc && ! empty(config('marketplace.cities.'.$loc)))
                    <div class="card p-4">
                        <h3 class="text-sm font-extrabold mb-2">{{ __('City in :district', ['district' => __($loc)]) }}</h3>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach (config('marketplace.cities.'.$loc) as $city)
                                <a href="{{ $with(['city' => request('city') === $city ? null : $city]) }}" class="chip {{ request('city') === $city ? '!bg-brand !text-white' : '' }}">{{ $city }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="GET" action="{{ route('listings.index') }}" class="card p-4 space-y-4">
                    @foreach (array_diff_key($params, array_flip(['min', 'max', 'condition', 'verified', 'member', 'urgent'])) as $k => $v)
                        @if (is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                    @endforeach
                    <div>
                        <h3 class="text-sm font-extrabold mb-2">{{ __('Price (Rs)') }}</h3>
                        <div class="flex items-center gap-2">
                            <input type="number" name="min" value="{{ request('min') }}" placeholder="{{ __('Min') }}" min="0" class="input !py-2 !text-sm">
                            <span class="text-faint">–</span>
                            <input type="number" name="max" value="{{ request('max') }}" placeholder="{{ __('Max') }}" min="0" class="input !py-2 !text-sm">
                        </div>
                    </div>
                    @if (! $category || $category->has_condition)
                        <div>
                            <h3 class="text-sm font-extrabold mb-2">{{ __('Condition') }}</h3>
                            <select name="condition" class="input !py-2 !text-sm">
                                <option value="">{{ __('Any') }}</option>
                                @foreach ($conditions as $c)<option value="{{ $c }}" @selected(request('condition') === $c)>{{ __($c) }}</option>@endforeach
                            </select>
                        </div>
                    @endif
                    <div class="space-y-2 text-[13.5px]">
                        <h3 class="text-sm font-extrabold">{{ __('Seller') }}</h3>
                        <label class="flex items-center gap-2"><input type="checkbox" name="verified" value="1" @checked(request('verified')) class="accent-brand w-4 h-4"> {{ __('Verified sellers only') }}</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="member" value="1" @checked(request('member')) class="accent-brand w-4 h-4"> {{ __('Members (shops & agents)') }}</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="urgent" value="1" @checked(request('urgent')) class="accent-brand w-4 h-4"> {{ __('Urgent sales') }}</label>
                    </div>
                    <button class="btn-primary w-full">{{ __('Apply filters') }}</button>
                </form>
            </div>
        </aside>

        {{-- Results --}}
        <section>
            <div class="flex flex-wrap items-end justify-between gap-3 mb-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold leading-tight">{{ $heading }}</h1>
                    <p class="text-sm text-muted mt-0.5">{{ number_format($listings->total()) }} {{ __(\Illuminate\Support\Str::plural('ad', $listings->total())) }}</p>
                </div>
                <div class="flex gap-2">
                    <button @click="filters = true" class="lg:hidden btn-ghost !py-2">@svg('lucide-sliders-horizontal', 'w-4 h-4') {{ __('Filters') }}</button>
                    <form method="GET" action="{{ route('listings.index') }}">
                        @foreach (array_diff_key($params, ['sort' => 1]) as $k => $v)
                            @if (is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                        @endforeach
                        <select name="sort" onchange="this.form.submit()" class="input !py-2 !text-sm !w-auto" aria-label="{{ __('Sort') }}">
                            @foreach ($sorts as $k => $label)<option value="{{ $k }}" @selected((string) request('sort') === (string) $k)>{{ $label }}</option>@endforeach
                        </select>
                    </form>
                </div>
            </div>

            @if ($chips)
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach ($chips as [$label, $patch])
                        <a href="{{ $with($patch) }}" class="chip !bg-white border border-line">{{ $label }} @svg('lucide-x', 'w-3.5 h-3.5')</a>
                    @endforeach
                    <a href="{{ route('listings.index') }}" class="hit text-sm font-semibold text-brand-dark self-center ml-1 hover:underline">{{ __('Clear all') }}</a>
                </div>
            @endif

            <x-banner-slot :banner="$banner" placement="search_top" class="mb-4" />

            <div class="space-y-2.5 sm:space-y-3">
                @forelse ($listings as $ad)
                    <x-ad-row :listing="$ad" />
                @empty
                    <div class="card p-10 text-center">
                        <span class="mx-auto w-14 h-14 rounded-full bg-lilac text-brand flex items-center justify-center">@svg('lucide-search', 'w-6 h-6')</span>
                        <p class="mt-3 text-lg font-extrabold">{{ __('No ads match your search') }}</p>
                        <p class="text-sm text-muted mt-1">{{ __('Try a different word, district or fewer filters.') }}</p>
                        <a href="{{ route('listings.index') }}" class="btn-primary mt-4">{{ __('Browse all ads') }}</a>
                    </div>
                @endforelse
            </div>

            <div class="mt-6">{{ $listings->onEachSide(1)->links() }}</div>
        </section>
    </div>
</div>
</x-layouts.app>
