<x-layouts.app :title="__(':name on 4U.lk', ['name' => $seller->name])">
<div class="max-w-6xl mx-auto px-4 py-5 sm:py-8">
    <div class="card p-5 flex flex-col sm:flex-row gap-4 sm:items-center">
        <span class="w-20 h-20 rounded-full brand-gradient text-white text-2xl font-bold flex items-center justify-center overflow-hidden shrink-0">
            @if ($seller->avatar)<img src="{{ asset($seller->avatar) }}" alt="" class="w-full h-full object-cover">@else{{ $seller->initials() }}@endif
        </span>
        <div class="flex-1">
            <h1 class="text-2xl font-extrabold flex flex-wrap items-center gap-2">{{ $seller->name }}
                @if ($seller->isVerified())<span class="badge bg-emerald-100 text-emerald-700 !text-[11px]">@svg('lucide-shield-check', 'w-3.5 h-3.5') {{ __('VERIFIED') }}</span>@endif
            </h1>
            <p class="text-sm text-muted mt-1">
                @svg('lucide-star', 'w-4 h-4 inline text-amber-500') {{ $rating ? number_format($rating, 1).' / 5' : __('No ratings yet') }} ({{ $reviews->count() }})
                · {{ __('Member since :date', ['date' => $seller->created_at->translatedFormat('F Y')]) }}
                @if ($seller->district) · {{ __($seller->district) }} @endif
            </p>
            <div class="mt-2 h-2 max-w-xs rounded-full bg-mist" title="{{ __('Trust score :score/100', ['score' => $seller->trust_score]) }}">
                <div class="h-2 rounded-full {{ $seller->trust_score >= 60 ? 'bg-emerald-500' : ($seller->trust_score >= 35 ? 'bg-amber-400' : 'bg-red-500') }}" style="width: {{ $seller->trust_score }}%"></div>
            </div>
            <p class="text-xs text-faint mt-1">{{ __('Seller reputation: :level', ['level' => $seller->trust_score >= 60 ? __('Good') : ($seller->trust_score >= 35 ? __('Building') : __('Low'))]) }}</p>
        </div>
        @if ($store)<a href="{{ route('stores.show', $store) }}" class="btn-primary">@svg('lucide-store', 'w-4 h-4') {{ __('Visit store') }}</a>@endif
    </div>

    <div class="mt-5 grid grid-cols-[minmax(0,1fr)] lg:grid-cols-[1fr_340px] gap-5 items-start">
        <section>
            <h2 class="text-lg font-extrabold mb-3">{{ __('Ads by :name', ['name' => $seller->name]) }}</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
                @forelse ($listings as $ad)<x-ad-tile :listing="$ad" />@empty<p class="col-span-full card p-8 text-center text-muted">{{ __('No live ads.') }}</p>@endforelse
            </div>
            <div class="mt-5">{{ $listings->links() }}</div>
        </section>
        <aside class="space-y-4">
            @if ($canReview)
                <form method="POST" action="{{ route('reviews.store', $seller) }}" class="card p-4 space-y-3" x-data="{ r: 0 }">
                    @csrf
                    <h2 class="font-extrabold">{{ __('Rate your experience') }}</h2>
                    <div class="flex gap-1">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button" @click="r = {{ $i }}" class="text-2xl" :class="r >= {{ $i }} ? 'text-amber-400' : 'text-line'" aria-label="{{ __(':count stars', ['count' => $i]) }}">★</button>
                        @endfor
                    </div>
                    <input type="hidden" name="rating" :value="r">
                    <textarea name="comment" rows="3" maxlength="800" class="input !text-sm" placeholder="{{ __('How was dealing with this seller?') }}"></textarea>
                    <button class="btn-primary w-full" :disabled="!r">{{ __('Post review') }}</button>
                </form>
            @endif
            <div class="card p-4">
                <h2 class="font-extrabold">{{ __('Reviews') }}</h2>
                <ul class="mt-2 divide-y divide-line-soft">
                    @forelse ($reviews as $r)
                        <li class="py-3">
                            <p class="text-amber-400 text-sm">{{ str_repeat('★', $r->rating) }}<span class="text-line">{{ str_repeat('★', 5 - $r->rating) }}</span></p>
                            @if ($r->comment)<p class="text-sm mt-0.5">{{ $r->comment }}</p>@endif
                            <p class="text-xs text-faint mt-0.5">{{ $r->reviewer->name }} · {{ $r->created_at->diffForHumans() }}</p>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-muted">{{ __('No reviews yet. Buyers who chat with this seller can leave one.') }}</li>
                    @endforelse
                </ul>
            </div>
        </aside>
    </div>
</div>
</x-layouts.app>
