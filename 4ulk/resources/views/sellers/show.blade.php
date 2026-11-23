<x-layouts.app :title="$seller->name.' on 4U.lk'">
<div class="max-w-6xl mx-auto px-4 py-5 sm:py-8">
    <div class="card p-5 flex flex-col sm:flex-row gap-4 sm:items-center">
        <span class="w-20 h-20 rounded-full brand-gradient text-white text-2xl font-bold flex items-center justify-center overflow-hidden shrink-0">
            @if ($seller->avatar)<img src="{{ asset($seller->avatar) }}" alt="" class="w-full h-full object-cover">@else{{ $seller->initials() }}@endif
        </span>
        <div class="flex-1">
            <h1 class="text-2xl font-extrabold flex flex-wrap items-center gap-2">{{ $seller->name }}
                @if ($seller->isVerified())<span class="badge bg-emerald-100 text-emerald-700 !text-[11px]">@svg('lucide-shield-check', 'w-3.5 h-3.5') VERIFIED</span>@endif
            </h1>
            <p class="text-sm text-muted mt-1">
                @svg('lucide-star', 'w-4 h-4 inline text-amber-500') {{ $rating ? number_format($rating, 1).' / 5' : 'No ratings yet' }} ({{ $reviews->count() }})
                · Member since {{ $seller->created_at->format('F Y') }}
                @if ($seller->district) · {{ $seller->district }} @endif
            </p>
            <div class="mt-2 h-2 max-w-xs rounded-full bg-mist" title="Trust score {{ $seller->trust_score }}/100">
                <div class="h-2 rounded-full {{ $seller->trust_score >= 60 ? 'bg-emerald-500' : ($seller->trust_score >= 35 ? 'bg-amber-400' : 'bg-red-500') }}" style="width: {{ $seller->trust_score }}%"></div>
            </div>
            <p class="text-xs text-faint mt-1">Seller reputation: {{ $seller->trust_score >= 60 ? 'Good' : ($seller->trust_score >= 35 ? 'Building' : 'Low') }}</p>
        </div>
        @if ($store)<a href="{{ route('stores.show', $store) }}" class="btn-primary">@svg('lucide-store', 'w-4 h-4') Visit store</a>@endif
    </div>

    <div class="mt-5 grid grid-cols-[minmax(0,1fr)] lg:grid-cols-[1fr_340px] gap-5 items-start">
        <section>
            <h2 class="text-lg font-extrabold mb-3">Ads by {{ $seller->name }}</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
                @forelse ($listings as $ad)<x-ad-tile :listing="$ad" />@empty<p class="col-span-full card p-8 text-center text-muted">No live ads.</p>@endforelse
            </div>
            <div class="mt-5">{{ $listings->links() }}</div>
        </section>
        <aside class="space-y-4">
            @if ($canReview)
                <form method="POST" action="{{ route('reviews.store', $seller) }}" class="card p-4 space-y-3" x-data="{ r: 0 }">
                    @csrf
                    <h2 class="font-extrabold">Rate your experience</h2>
                    <div class="flex gap-1">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button" @click="r = {{ $i }}" class="text-2xl" :class="r >= {{ $i }} ? 'text-amber-400' : 'text-line'" aria-label="{{ $i }} stars">★</button>
                        @endfor
                    </div>
                    <input type="hidden" name="rating" :value="r">
                    <textarea name="comment" rows="3" maxlength="800" class="input !text-sm" placeholder="How was dealing with this seller?"></textarea>
                    <button class="btn-primary w-full" :disabled="!r">Post review</button>
                </form>
            @endif
            <div class="card p-4">
                <h2 class="font-extrabold">Reviews</h2>
                <ul class="mt-2 divide-y divide-line-soft">
                    @forelse ($reviews as $r)
                        <li class="py-3">
                            <p class="text-amber-400 text-sm">{{ str_repeat('★', $r->rating) }}<span class="text-line">{{ str_repeat('★', 5 - $r->rating) }}</span></p>
                            @if ($r->comment)<p class="text-sm mt-0.5">{{ $r->comment }}</p>@endif
                            <p class="text-xs text-faint mt-0.5">{{ $r->reviewer->name }} · {{ $r->created_at->diffForHumans() }}</p>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-muted">No reviews yet. Buyers who chat with this seller can leave one.</li>
                    @endforelse
                </ul>
            </div>
        </aside>
    </div>
</div>
</x-layouts.app>
