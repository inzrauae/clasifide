<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Order;
use App\Services\ImageService;
use App\Services\ListingPublisher;
use App\Services\SafetyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ListingController extends Controller
{
    public const CONDITIONS = ['Brand New', 'Used', 'Reconditioned'];

    // ================================================================ browse

    public function index(Request $request)
    {
        $categories = Category::allCached();
        $category = $request->filled('cat') ? $categories->firstWhere('slug', $request->cat) : null;
        $loc = in_array($request->loc, array_keys(config('marketplace.districts')), true) ? $request->loc : null;
        $q = trim((string) $request->q);

        // Everything except the category filter (used for the category counts)
        $base = Listing::live()
            ->when($loc, fn ($w) => $w->where('district', $loc))
            ->when($loc && $request->filled('city'), fn ($w) => $w->where('city', $request->city))
            ->when($q !== '', function ($w) use ($q) {
                foreach (preg_split('/\s+/', $q) as $term) {
                    $w->where(fn ($x) => $x->where('title', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%"));
                }
            })
            ->when($request->filled('min'), fn ($w) => $w->where('price', '>=', (int) $request->min))
            ->when($request->filled('max'), fn ($w) => $w->where('price', '<=', (int) $request->max))
            ->when(in_array($request->condition, self::CONDITIONS, true), fn ($w) => $w->where('condition', $request->condition))
            ->when($request->boolean('urgent'), fn ($w) => $w->where('urgent_until', '>', now()))
            ->when($request->boolean('verified'), fn ($w) => $w->whereHas('user', fn ($u) => $u->whereNotNull('verified_at')))
            ->when($request->boolean('member'), fn ($w) => $w->whereHas('user.activeSubscription'));

        $catCounts = (clone $base)->selectRaw('category_id, count(*) as n')->groupBy('category_id')->pluck('n', 'category_id');

        $query = (clone $base)->when($category, fn ($w) => $w->where('category_id', $category->id));
        $districtCounts = Listing::live()->when($category, fn ($w) => $w->where('category_id', $category->id))
            ->selectRaw('district, count(*) as n')->groupBy('district')->pluck('n', 'district');

        match ($request->sort) {
            'date' => $query->orderByDesc(DB::raw('COALESCE(bumped_at, published_at, created_at)')),
            'price_asc' => $query->orderByRaw('price IS NULL')->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->ranked(),
        };

        $listings = $query->with(['images', 'category', 'user.activeSubscription', 'store'])
            ->paginate(20)->withQueryString();

        return view('listings.index', [
            'listings' => $listings,
            'categories' => $categories,
            'category' => $category,
            'loc' => $loc,
            'q' => $q,
            'catCounts' => $catCounts,
            'districtCounts' => $districtCounts,
            'conditions' => self::CONDITIONS,
            'banner' => Banner::pick('search_top'),
        ]);
    }

    public function show(Request $request, Listing $listing, ?string $slug = null)
    {
        $user = $request->user();
        $isOwner = $user && ($user->id === $listing->user_id || $user->isAdmin());

        if (! $listing->isLive() && ! $isOwner) {
            return response()->view('listings.gone', [], 410);
        }
        if ($slug !== $listing->slug) {
            return redirect($listing->url(), 301);
        }

        // One view per visitor per ad per session
        $seen = $request->session()->get('seen_ads', []);
        if (! $isOwner && ! in_array($listing->id, $seen, true)) {
            $listing->track('views');
            $listing->views_count++;
            $request->session()->push('seen_ads', $listing->id);
        }

        $listing->load(['images', 'category', 'store', 'user.activeSubscription.package']);
        $seller = $listing->user;

        $similar = Listing::live()->with(['images', 'category'])
            ->whereKeyNot($listing->id)
            ->where('category_id', $listing->category_id)
            ->orderByRaw('CASE WHEN district = ? THEN 0 ELSE 1 END', [$listing->district])
            ->ranked()->limit(8)->get();

        return view('listings.show', [
            'listing' => $listing,
            'seller' => $seller,
            'isOwner' => $isOwner,
            'saved' => $user ? $user->favorites()->whereKey($listing->id)->exists() : false,
            'rating' => $seller->ratingAverage(),
            'reviewsCount' => $seller->reviewsReceived()->where('is_visible', true)->count(),
            'sellerAds' => $seller->listings()->live()->count(),
            'similar' => $similar,
            'banner' => Banner::pick('listing_sidebar'),
        ]);
    }

    // ================================================================ post

    public function create(Request $request)
    {
        $user = $request->user();
        $used = $user->listings()->whereIn('status', ['active', 'pending', 'pending_payment'])->count();

        return view('listings.form', [
            'listing' => new Listing(['district' => $user->district, 'phone' => $user->phone, 'whatsapp' => $user->whatsapp, 'contact_name' => $user->name]),
            'categories' => Category::allCached(),
            'conditions' => self::CONDITIONS,
            'limitReached' => $used >= $user->activeAdLimit(),
            'limit' => $user->activeAdLimit(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_if($request->filled('website'), 422); // honeypot

        if ($user->listings()->whereIn('status', ['active', 'pending', 'pending_payment'])->count() >= $user->activeAdLimit()) {
            return redirect()->route('pricing')->with('error', 'You have reached the active ad limit for your package. Upgrade to post more ads.');
        }

        $data = $this->validated($request);
        $listing = new Listing($data['fields']);
        $listing->user_id = $user->id;
        $listing->store_id = $request->boolean('in_store') ? $user->stores()->value('id') : null;
        $listing->slug = Str::slug(Str::limit($listing->title, 70, '')) ?: 'ad';
        $listing->fingerprint = SafetyService::fingerprint($listing->title, $listing->category_id);

        if ($dup = SafetyService::findDuplicate($user, $listing->fingerprint, $listing->phone)) {
            return back()->withInput()->withErrors(['title' => 'You already have an ad with this title. Edit or renew that ad instead of posting it again.'])
                ->with('duplicate_url', $dup->url());
        }

        $listing->setRelation('category', Category::find($listing->category_id));
        $fee = ListingPublisher::feeDue($listing, $user);

        DB::transaction(function () use ($listing, $request, $user, $fee) {
            $listing->status = 'draft';
            $listing->save();
            $this->saveImages($listing, $request);

            if ($fee > 0) {
                $listing->status = 'pending_payment';
            } else {
                ListingPublisher::consumePremiumCredit($listing, $user);
                ListingPublisher::decideStatus($listing, $user);
            }
            $listing->save();
        });

        if ($listing->status === 'pending_payment') {
            $order = Order::create([
                'user_id' => $user->id,
                'type' => 'posting_fee',
                'description' => "Premium ad fee – {$listing->category->name}: ".Str::limit($listing->title, 60),
                'amount' => $fee,
                'meta' => ['listing_id' => $listing->id],
            ]);

            return redirect()->route('checkout.show', $order)
                ->with('status', "{$listing->category->name} ads need a one-time premium fee to go live.");
        }

        $msg = $listing->status === 'active'
            ? 'Your ad is live! Promote it to reach more buyers.'
            : 'Thanks! Your ad is being reviewed and will go live shortly.';

        return redirect()->route('promote.show', $listing)->with('status', $msg);
    }

    public function edit(Request $request, Listing $listing)
    {
        $this->authorizeOwner($request, $listing);

        return view('listings.form', [
            'listing' => $listing->load('images'),
            'categories' => Category::allCached(),
            'conditions' => self::CONDITIONS,
            'limitReached' => false,
            'limit' => $request->user()->activeAdLimit(),
        ]);
    }

    public function update(Request $request, Listing $listing)
    {
        $this->authorizeOwner($request, $listing);
        $user = $request->user();
        $data = $this->validated($request, $listing);

        $listing->fill($data['fields']);
        $listing->slug = Str::slug(Str::limit($listing->title, 70, '')) ?: 'ad';
        $listing->fingerprint = SafetyService::fingerprint($listing->title, $listing->category_id);
        $listing->store_id = $request->boolean('in_store') ? $user->stores()->value('id') : null;

        if (SafetyService::findDuplicate($user, $listing->fingerprint, $listing->phone, $listing->id)) {
            return back()->withInput()->withErrors(['title' => 'Another of your ads already uses this title.']);
        }

        DB::transaction(function () use ($listing, $request, $user) {
            $remove = collect($request->input('remove_images', []))->map(fn ($id) => (int) $id);
            $listing->images()->whereIn('id', $remove)->get()->each(function (ListingImage $img) {
                ImageService::delete($img->path);
                $img->delete();
            });
            $this->saveImages($listing, $request);

            // Re-check edited content; sold/expired/payment-due ads keep their status
            if (in_array($listing->status, ['active', 'pending', 'rejected'], true)) {
                $wasLive = $listing->status === 'active';
                $expires = $listing->expires_at;
                ListingPublisher::decideStatus($listing, $user);
                if ($wasLive && $listing->status === 'active') {
                    $listing->expires_at = $expires; // editing does not extend the ad
                }
            }
            $listing->save();
        });

        return redirect()->route('dashboard.ads')->with('status', $listing->status === 'active' ? 'Ad updated.' : 'Ad updated and sent for review.');
    }

    public function destroy(Request $request, Listing $listing)
    {
        $this->authorizeOwner($request, $listing);
        $listing->delete();

        return redirect()->route('dashboard.ads')->with('status', 'Ad deleted.');
    }

    public function markSold(Request $request, Listing $listing)
    {
        $this->authorizeOwner($request, $listing);
        $listing->update(['status' => 'sold']);

        return back()->with('status', 'Marked as sold. Congratulations!');
    }

    /** Free re-publish for expired ads (keeps counters and URL). */
    public function renew(Request $request, Listing $listing)
    {
        $this->authorizeOwner($request, $listing);
        $expired = $listing->status === 'expired' || ($listing->status === 'active' && $listing->expires_at?->isPast());
        abort_unless($expired, 422);

        $listing->update([
            'status' => 'active',
            'expires_at' => now()->addDays(config('marketplace.ad_lifetime_days')),
            'bumped_at' => now(),
        ]);

        return back()->with('status', 'Ad renewed for another '.config('marketplace.ad_lifetime_days').' days.');
    }

    // ================================================================ helpers

    private function authorizeOwner(Request $request, Listing $listing): void
    {
        abort_unless($request->user()->id === $listing->user_id || $request->user()->isAdmin(), 403);
    }

    private function validated(Request $request, ?Listing $listing = null): array
    {
        $existing = $listing ? $listing->images()->count() - count($request->input('remove_images', [])) : 0;
        $maxNew = max(0, config('marketplace.max_photos') - $existing);
        $districts = array_keys(config('marketplace.districts'));

        $data = $request->validate([
            'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'min:8', 'max:90'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'price' => ['nullable', 'integer', 'min:0', 'max:9999999999'],
            'price_unit' => ['nullable', Rule::in(['/month', '/perch', '/acre', '/day', '/each', '/hour'])],
            'negotiable' => ['nullable', 'boolean'],
            'condition' => ['nullable', Rule::in(self::CONDITIONS)],
            'district' => ['required', Rule::in($districts)],
            'city' => ['nullable', 'string', 'max:60'],
            'contact_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'regex:/^0\d{9}$/'],
            'whatsapp' => ['nullable', 'regex:/^0\d{9}$/'],
            'specs' => ['nullable', 'array'],
            'specs.*' => ['nullable', 'string', 'max:120'],
            'photos' => ['nullable', 'array', 'max:'.$maxNew],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [
            'phone.regex' => 'Enter a mobile number like 0771234567.',
            'photos.max' => 'You can add up to '.config('marketplace.max_photos').' photos.',
        ]);

        $category = Category::find($data['category_id']);
        $specs = collect($data['specs'] ?? [])->map(fn ($v) => trim((string) $v))->filter()->all();

        return ['fields' => [
            'category_id' => $category->id,
            'title' => Str::squish($data['title']),
            'description' => trim($data['description']),
            'price' => $data['price'] ?? null,
            'price_unit' => $data['price_unit'] ?? null,
            'negotiable' => (bool) ($data['negotiable'] ?? false),
            'condition' => $category->has_condition ? ($data['condition'] ?? null) : null,
            'district' => $data['district'],
            'city' => $data['city'] ?? null,
            'contact_name' => $data['contact_name'],
            'phone' => $data['phone'],
            'whatsapp' => $data['whatsapp'] ?? null,
            'specs' => $specs ?: null,
        ]];
    }

    private function saveImages(Listing $listing, Request $request): void
    {
        $sort = (int) $listing->images()->max('sort');
        foreach ((array) $request->file('photos', []) as $file) {
            $saved = ImageService::store($file, 'ads');
            $listing->images()->create(['path' => $saved['path'], 'hash' => $saved['hash'], 'sort' => ++$sort]);
        }
    }
}
