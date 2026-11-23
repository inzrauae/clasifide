@php($credits = $subscription?->promotions_left ?? 0)
<x-layouts.app title="Promote your ad">
<div class="max-w-4xl mx-auto px-4 py-6 sm:py-8">
    <div class="card p-4 flex gap-4 items-center">
        <div class="relative w-24 h-20 rounded-lg overflow-hidden shrink-0"><x-ad-image :listing="$listing" /></div>
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-wider text-faint">{{ $listing->statusLabel() }}</p>
            <a href="{{ $listing->url() }}" class="block font-bold truncate hover:text-brand-dark">{{ $listing->title }}</a>
            <p class="text-brand-dark font-extrabold">{{ $listing->priceLabel() }}</p>
        </div>
        <a href="{{ $listing->url() }}" class="ml-auto btn-ghost hidden sm:inline-flex">View ad</a>
    </div>

    <h1 class="mt-6 text-2xl sm:text-3xl font-extrabold">Get more buyers with a promotion</h1>
    <p class="text-muted mt-1">Promoted ads get up to 10× more views. Pay once per promotion, or use credits from your package.</p>
    @if ($subscription)
        <p class="mt-3 inline-flex items-center gap-2 rounded-full bg-mist px-3 py-1.5 text-sm font-bold text-[#4C1D95]">@svg('lucide-zap', 'w-4 h-4') {{ $credits }} promotion credits left on your {{ $subscription->package->name }} package</p>
    @endif

    <div class="mt-5 grid sm:grid-cols-2 gap-4">
        @foreach ($promotions as $key => $p)
            <form method="POST" action="{{ route('promote.store', $listing) }}" class="card p-5 flex flex-col">
                @csrf
                <input type="hidden" name="type" value="{{ $key }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold">{{ $p['name'] }}</h2>
                        <p class="text-sm text-muted mt-0.5">{{ $p['blurb'] }}</p>
                    </div>
                    <span class="w-10 h-10 rounded-xl bg-lilac text-brand flex items-center justify-center shrink-0">
                        @svg(['top' => 'lucide-trending-up', 'featured' => 'lucide-star', 'urgent' => 'lucide-zap', 'bump' => 'lucide-rocket'][$key] ?? 'lucide-rocket', 'w-5 h-5')
                    </span>
                </div>
                <p class="mt-3 text-2xl font-extrabold text-brand-dark">Rs {{ number_format($p['price']) }} <span class="text-sm font-semibold text-muted">{{ $p['days'] ? '/ '.$p['days'].' days' : 'one time' }}</span></p>
                <div class="mt-4 flex gap-2 mt-auto pt-4">
                    <button name="pay_with" value="pay" class="btn-primary flex-1">Pay Rs {{ number_format($p['price']) }}</button>
                    @if ($credits >= $p['credits'])
                        <button name="pay_with" value="credit" class="btn-ghost flex-1">Use {{ $p['credits'] }} credit{{ $p['credits'] > 1 ? 's' : '' }}</button>
                    @endif
                </div>
            </form>
        @endforeach
    </div>

    @unless ($subscription)
        <div class="mt-6 rounded-2xl bg-night text-white p-5 flex flex-col sm:flex-row gap-4 sm:items-center">
            <div class="flex-1">
                <p class="font-extrabold text-lg">Selling regularly?</p>
                <p class="text-sm text-white/70">The Starter package (Rs 990/month) includes 5 promotion credits, a store page and a MEMBER badge.</p>
            </div>
            <a href="{{ route('pricing') }}" class="btn-sun">See packages</a>
        </div>
    @endunless

    <p class="mt-6 text-center"><a href="{{ route('dashboard.ads') }}" class="text-sm font-semibold text-muted hover:text-ink">Skip for now</a></p>
</div>
</x-layouts.app>
