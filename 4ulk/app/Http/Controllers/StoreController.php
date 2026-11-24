<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Store;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function show(Request $request, Store $store)
    {
        abort_unless($store->is_active, 404);

        if (! $request->session()->has('store_'.$store->id)) {
            $store->increment('views_count');
            $request->session()->put('store_'.$store->id, true);
        }

        $catId = $request->query('cat');
        $base = $store->listings()->live();
        $categories = Category::allCached()->whereIn('id', (clone $base)->distinct()->pluck('category_id'));

        $listings = (clone $base)->with(['images', 'category'])
            ->when($catId, fn ($q) => $q->where('category_id', $catId))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->q.'%'))
            ->ranked()->paginate(24)->withQueryString();

        $owner = $store->user;

        return view('stores.show', [
            'store' => $store,
            'owner' => $owner,
            'listings' => $listings,
            'categories' => $categories,
            'rating' => $owner->ratingAverage(),
            'reviewsCount' => $owner->reviewsReceived()->where('is_visible', true)->count(),
        ]);
    }

    public function edit(Request $request)
    {
        $user = $request->user();

        return view('dashboard.store', [
            'store' => $user->stores()->first() ?? new Store(['phone' => $user->phone, 'whatsapp' => $user->whatsapp, 'district' => $user->district]),
            'allowed' => $user->storeLimit() > 0 || $user->isAdmin(),
            'districts' => array_keys(config('marketplace.districts')),
        ]);
    }

    public function save(Request $request)
    {
        $user = $request->user();
        abort_unless($user->storeLimit() > 0 || $user->isAdmin(), 403, __('Stores are part of the Starter package and above.'));

        $store = $user->stores()->first() ?? new Store;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'alpha_dash', 'max:60', Rule::unique('stores')->ignore($store->id)],
            'tagline' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:3000'],
            'phone' => ['nullable', 'regex:/^0\d{9}$/'],
            'whatsapp' => ['nullable', 'regex:/^0\d{9}$/'],
            'email' => ['nullable', 'email'],
            'website' => ['nullable', 'url', 'max:150'],
            'address' => ['nullable', 'string', 'max:200'],
            'district' => ['nullable', Rule::in(array_keys(config('marketplace.districts')))],
            'city' => ['nullable', 'string', 'max:60'],
            'opening_hours' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'banner' => ['nullable', 'image', 'max:6144'],
        ]);

        $slug = Str::slug($data['slug'] ?? '') ?: Str::slug($data['name']);
        if (Store::where('slug', $slug)->whereKeyNot($store->id)->exists()) {
            $slug .= '-'.Str::lower(Str::random(4));
        }

        foreach (['logo' => 400, 'banner' => 1600] as $field => $size) {
            if ($request->hasFile($field)) {
                ImageService::delete($store->{$field});
                $store->{$field} = ImageService::store($request->file($field), 'stores', $size)['path'];
            }
        }

        $store->fill(collect($data)->except(['logo', 'banner', 'slug'])->all());
        $store->slug = $slug;
        $store->user_id = $user->id;
        $store->save();

        // New store: move this seller's ads into it
        if ($store->wasRecentlyCreated) {
            $user->listings()->whereNull('store_id')->update(['store_id' => $store->id]);
        }

        return redirect()->route('dashboard.store')->with('status', __('Store saved. Your store page is live at :url', ['url' => route('stores.show', $store)]));
    }
}
