@props(['loc' => null, 'q' => '', 'cat' => null, 'big' => false, 'counts' => []])
@php($districts = config('marketplace.districts'))
<form action="{{ route('listings.index') }}" method="GET" role="search"
      x-data="locationPicker(@js($districts), @js($loc))" x-ref="form"
      class="flex flex-col sm:flex-row gap-2 bg-white rounded-2xl {{ $big ? 'p-2 shadow-[0_24px_60px_-24px_rgba(14,8,32,.7)]' : 'p-1.5 border border-line' }}">
    <input type="hidden" name="loc" :value="current || ''">
    @if ($cat)<input type="hidden" name="cat" value="{{ $cat }}">@endif

    <button type="button" @click="open = true" class="tap flex items-center gap-2 px-3.5 py-3 rounded-xl bg-mist text-sm font-semibold text-ink sm:w-[210px] shrink-0">
        @svg('lucide-map-pin', 'w-4 h-4 text-brand shrink-0')
        <span class="truncate flex-1 text-left" x-text="current || 'All of Sri Lanka'">{{ $loc ?: 'All of Sri Lanka' }}</span>
        @svg('lucide-chevron-down', 'w-4 h-4 text-faint')
    </button>
    <div class="flex flex-1 gap-2">
        <label class="flex-1 flex items-center gap-2 px-3 rounded-xl focus-within:bg-[#FAF9FF]">
            @svg('lucide-search', 'w-4 h-4 text-faint shrink-0')
            <span class="sr-only">What are you looking for?</span>
            <input name="q" value="{{ $q }}" placeholder="What are you looking for?" x-init="if (innerWidth < 640) $el.placeholder = 'Search anything…'" autocomplete="off" enterkeyhint="search"
                   class="w-full py-3 bg-transparent text-[15px] text-ink placeholder-faint focus:outline-none">
        </label>
        <button type="submit" class="tap shrink-0 px-5 sm:px-7 rounded-xl bg-sun hover:bg-sun-dark text-ink font-extrabold text-sm">Search</button>
    </div>

    {{-- District sheet --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak @keydown.escape.window="open = false" @click="open = false"
             class="fixed inset-0 z-[80] bg-deep/50 backdrop-blur-[2px] flex items-end sm:items-center justify-center">
            <div @click.stop class="sheet bg-white w-full sm:max-w-2xl rounded-t-[22px] sm:rounded-2xl max-h-[88dvh] flex flex-col shadow-2xl pb-safe" role="dialog" aria-label="Choose location">
                <div class="sm:hidden mx-auto mt-2.5 w-10 h-1.5 rounded-full bg-[#E4DDF5]"></div>
                <div class="flex items-center justify-between px-5 pt-3 sm:pt-4 pb-3 border-b border-line-soft">
                    <h3 class="text-base font-bold text-ink">Choose a district</h3>
                    <button type="button" @click="open = false" class="tap p-2 -mr-2 rounded-full hover:bg-mist" aria-label="Close">@svg('lucide-x', 'w-5 h-5 text-muted')</button>
                </div>
                <div class="overflow-y-auto overscroll-contain p-4">
                    <div class="flex gap-2 mb-3">
                        <label class="flex-1 flex items-center gap-2 px-3 py-2.5 rounded-xl bg-mist">
                            @svg('lucide-search', 'w-4 h-4 text-brand')
                            <input x-model="q" placeholder="Search district" class="bg-transparent w-full text-sm focus:outline-none">
                        </label>
                        <button type="button" @click="nearMe(d => { current = d; open = false; $nextTick(() => $refs.form.submit()) })"
                                class="tap shrink-0 inline-flex items-center gap-1.5 px-3 rounded-xl border border-line text-sm font-bold text-brand-dark hover:bg-mist">
                            @svg('lucide-locate-fixed', 'w-4 h-4') <span x-text="locating ? 'Finding…' : 'Near me'">Near me</span>
                        </button>
                    </div>
                    <button type="button" x-show="!q" @click="current = null; open = false"
                            class="w-full flex items-center justify-between px-3 py-3 rounded-xl text-sm font-bold"
                            :class="!current ? 'bg-[#EDE9FE] text-brand-dark' : 'hover:bg-[#F8F6FD] text-ink'">All of Sri Lanka</button>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-2">
                        <template x-for="d in list" :key="d">
                            <button type="button" @click="current = d; open = false"
                                    class="flex items-center justify-between px-3 py-3 rounded-xl text-sm text-left"
                                    :class="current === d ? 'bg-[#EDE9FE] text-brand-dark font-bold' : 'hover:bg-[#F8F6FD] text-ink'">
                                <span x-text="d"></span>
                                <span class="flex items-center gap-1 text-xs text-faint"><span x-text="(@js($counts))[d] || ''"></span>@svg('lucide-chevron-right', 'w-3.5 h-3.5')</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </template>
</form>
