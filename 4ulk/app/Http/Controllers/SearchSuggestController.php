<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** Type-ahead for the search bar: matching categories and live ads. */
class SearchSuggestController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = Str::squish((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json(['categories' => [], 'ads' => []]);
        }
        $q = mb_substr($q, 0, 60);
        $needle = mb_strtolower($q);
        $catSlug = (string) $request->query('cat');

        $categories = Category::allCached()
            ->filter(fn ($c) => str_contains(mb_strtolower($c->name), $needle) || str_contains(mb_strtolower($c->slug), $needle))
            ->take(3)
            ->map(fn ($c) => ['name' => $c->name, 'icon' => $c->icon, 'url' => route('listings.index', ['cat' => $c->slug])])
            ->values();

        // Ads change slowly enough that a short cache keeps typing cheap on shared hosting
        $ads = Cache::remember('suggest:'.app()->getLocale().':'.$catSlug.':'.md5($needle), 60, function () use ($q, $catSlug) {
            $catId = $catSlug !== '' ? Category::allCached()->firstWhere('slug', $catSlug)?->id : null;
            $query = Listing::live()->with(['images', 'category'])
                ->when($catId, fn ($w) => $w->where('category_id', $catId));
            foreach (preg_split('/\s+/', $q) as $term) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
                $query->where('title', 'like', $like);
            }

            return $query->ranked()->limit(6)->get()->map(fn (Listing $l) => [
                'title' => $l->title,
                'url' => $l->url(),
                'price' => $l->priceLabel(),
                'place' => __($l->district),
                'category' => $l->category?->name,
                'image' => $l->coverUrl(),
            ])->all();
        });

        return response()->json(['categories' => $categories, 'ads' => $ads])
            ->header('Cache-Control', 'private, max-age=30');
    }
}
