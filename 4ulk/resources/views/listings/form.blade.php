@php
    $editing = $listing->exists;
    $catsJs = $categories->mapWithKeys(fn ($c) => [$c->id => [
        'name' => $c->name, 'fields' => $c->specFields(), 'condition' => $c->has_condition, 'fee' => $c->posting_fee, 'free' => $c->free_ads_per_month,
    ]]);
    $specs = old('specs', $listing->specs ?? []);
    $existing = $editing ? $listing->images->map(fn ($i) => ['id' => $i->id, 'url' => $i->url()])->values() : collect();
@endphp
<x-layouts.app :title="$editing ? __('Edit ad') : __('Post an ad')">
<div class="max-w-3xl mx-auto px-4 py-5 sm:py-8">
    <h1 class="text-2xl sm:text-3xl font-extrabold">{{ $editing ? __('Edit your ad') : __('Post an ad for free') }}</h1>
    <p class="text-muted text-sm mt-1">{{ $editing ? __('Changes to a live ad are checked again before they show.') : __('Clear photos and an honest description sell faster.') }}</p>

    @if ($limitReached)
        <div class="mt-5 rounded-2xl bg-[#FFFBEB] border border-[#FDE68A] p-4 text-sm text-[#78350F]">
            <p class="font-bold">{{ __('You have :count live ads, the limit for your package.', ['count' => $limit]) }}</p>
            <p class="mt-1">{!! __('Mark sold items as sold, delete old ads, or :link to post more.', ['link' => '<a href="'.e(route('pricing')).'" class="font-bold underline">'.e(__('upgrade your package')).'</a>']) !!}</p>
        </div>
    @endif

    @if (session('duplicate_url'))
        <div class="mt-5 rounded-2xl bg-red-50 border border-red-200 p-4 text-sm text-red-700">
            {!! __('This looks like a duplicate of :link. Duplicate ads are removed to keep 4U.lk clean.', ['link' => '<a href="'.e(session('duplicate_url')).'" class="font-bold underline">'.e(__('your existing ad')).'</a>']) !!}
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('listings.update', $listing) : route('listings.store') }}" enctype="multipart/form-data"
          class="mt-5 space-y-5"
          x-data="adForm({ cat: @js((string) old('category_id', $listing->category_id ?? '')), district: @js(old('district', $listing->district)), city: @js(old('city', $listing->city)), cats: @js($catsJs), cities: @js(config('marketplace.cities')) })">
        @csrf
        @if ($editing) @method('PUT') @endif
        <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

        {{-- 1. Category --}}
        <section class="card p-4 sm:p-6">
            <h2 class="font-extrabold flex items-center gap-2"><span class="w-6 h-6 rounded-full bg-brand text-white text-xs flex items-center justify-center">1</span> {{ __('Category') }}</h2>
            <div class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-2">
                @foreach ($categories as $c)
                    <label class="cursor-pointer">
                        <input type="radio" name="category_id" value="{{ $c->id }}" x-model="cat" class="peer sr-only" @checked((string) old('category_id', $listing->category_id) === (string) $c->id)>
                        <span class="flex items-center gap-2 rounded-xl border border-line px-3 py-2.5 text-[13.5px] font-semibold peer-checked:border-brand peer-checked:bg-mist peer-checked:text-brand-dark hover:bg-mist">
                            @svg('lucide-'.$c->icon, 'w-4 h-4 text-brand shrink-0') <span class="leading-tight">{{ $c->name }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('category_id')<p class="error">{{ $message }}</p>@enderror
            <template x-if="category && category.fee > 0">
                <p class="mt-3 rounded-xl bg-mist px-3 py-2 text-[13px] text-[#4C1D95]">
                    @svg('lucide-crown', 'w-4 h-4 inline text-brand')
                    <span x-text="@js(__(':category ads are premium:')).replace(':category', category.name)"></span>
                    <template x-if="category.free > 0"><span x-text="@js(__('your first :count each month are free, then')).replace(':count', category.free)"></span></template>
                    <b x-text="'Rs ' + category.fee.toLocaleString()"></b> {{ __('per ad. Packages include free premium ads.') }}
                </p>
            </template>
        </section>

        {{-- 2. Details --}}
        <section class="card p-4 sm:p-6 space-y-4">
            <h2 class="font-extrabold flex items-center gap-2"><span class="w-6 h-6 rounded-full bg-brand text-white text-xs flex items-center justify-center">2</span> {{ __('Details') }}</h2>

            <div>
                <label class="label" for="title">{{ __('Title') }}</label>
                <input id="title" name="title" value="{{ old('title', $listing->title) }}" maxlength="90" required class="input" placeholder="{{ __('e.g. Toyota Aqua G 2015, single owner') }}">
                @error('title')<p class="error">{{ $message }}</p>@enderror
            </div>

            <template x-if="category && category.condition">
                <div>
                    <span class="label">{{ __('Condition') }}</span>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($conditions as $cond)
                            <label class="cursor-pointer"><input type="radio" name="condition" value="{{ $cond }}" class="peer sr-only" @checked(old('condition', $listing->condition ?? 'Used') === $cond)>
                                <span class="inline-block rounded-full border border-line px-4 py-2 text-sm font-semibold peer-checked:bg-brand peer-checked:text-white peer-checked:border-brand">{{ __($cond) }}</span></label>
                        @endforeach
                    </div>
                </div>
            </template>

            {{-- Category-specific fields --}}
            <div class="grid sm:grid-cols-2 gap-3" x-show="category && category.fields.length">
                <template x-for="f in (category ? category.fields : [])" :key="f.name">
                    <div>
                        <label class="label" x-text="f.name"></label>
                        <template x-if="f.type === 'select'">
                            <select :name="'specs[' + f.name + ']'" class="input">
                                <option value="">{{ __('Select') }}</option>
                                <template x-for="o in f.options" :key="o"><option :value="o" x-text="o" :selected="(@js($specs))[f.name] === o"></option></template>
                            </select>
                        </template>
                        <template x-if="f.type !== 'select'">
                            <input :name="'specs[' + f.name + ']'" :value="(@js($specs))[f.name] || ''" :inputmode="f.type === 'number' ? 'decimal' : 'text'" maxlength="120" class="input">
                        </template>
                    </div>
                </template>
            </div>

            <div>
                <label class="label" for="description">{{ __('Description') }}</label>
                <textarea id="description" name="description" rows="6" maxlength="5000" required class="input" placeholder="{{ __('Condition, features, reason for selling, what\'s included…') }}">{{ old('description', $listing->description) }}</textarea>
                <p class="help">{{ __('Do not include links or ask for advance payments – such ads are held for review.') }}</p>
                @error('description')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="grid sm:grid-cols-[1fr_auto] gap-3 items-end">
                <div>
                    <label class="label" for="price">{{ __('Price (Rs)') }}</label>
                    <input id="price" name="price" type="number" min="0" inputmode="numeric" value="{{ old('price', $listing->price) }}" class="input" placeholder="{{ __('Leave empty for \'Price on request\'') }}">
                    @error('price')<p class="error">{{ $message }}</p>@enderror
                </div>
                <select name="price_unit" class="input sm:w-36" aria-label="{{ __('Price unit') }}">
                    <option value="">{{ __('Total') }}</option>
                    @foreach (['/month', '/perch', '/acre', '/day', '/hour', '/each'] as $u)<option value="{{ $u }}" @selected(old('price_unit', $listing->price_unit) === $u)>{{ __($u) }}</option>@endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="hidden" name="negotiable" value="0"><input type="checkbox" name="negotiable" value="1" @checked(old('negotiable', $listing->negotiable)) class="accent-brand w-4 h-4"> {{ __('Price is negotiable') }}</label>
        </section>

        {{-- 3. Photos --}}
        <section class="card p-4 sm:p-6" x-data="photoPicker({{ config('marketplace.max_photos') }}, @js($existing))">
            <h2 class="font-extrabold flex items-center gap-2"><span class="w-6 h-6 rounded-full bg-brand text-white text-xs flex items-center justify-center">3</span> {{ __('Photos') }} <span class="text-xs font-semibold text-muted">({{ __('up to :count', ['count' => config('marketplace.max_photos')]) }})</span></h2>
            <div class="mt-3 grid grid-cols-3 sm:grid-cols-6 gap-2">
                <template x-for="img in existing" :key="img.id">
                    <div class="relative aspect-square rounded-xl overflow-hidden border border-line" :class="removed.includes(img.id) && 'opacity-30'">
                        <img :src="img.url" class="w-full h-full object-cover" alt="">
                        <button type="button" @click="toggleExisting(img.id)" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/60 text-white flex items-center justify-center" aria-label="{{ __('Remove photo') }}">@svg('lucide-x', 'w-3.5 h-3.5')</button>
                        <template x-if="removed.includes(img.id)"><input type="hidden" name="remove_images[]" :value="img.id"></template>
                    </div>
                </template>
                <template x-for="(src, i) in previews" :key="src">
                    <div class="relative aspect-square rounded-xl overflow-hidden border border-line">
                        <img :src="src" class="w-full h-full object-cover" alt="">
                        <button type="button" @click="drop(i)" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/60 text-white flex items-center justify-center" aria-label="{{ __('Remove photo') }}">@svg('lucide-x', 'w-3.5 h-3.5')</button>
                    </div>
                </template>
                <label x-show="count < {{ config('marketplace.max_photos') }}" class="aspect-square rounded-xl border-2 border-dashed border-[#C4B5FD] flex flex-col items-center justify-center text-brand-dark text-xs font-bold cursor-pointer hover:bg-mist">
                    @svg('lucide-image-plus', 'w-6 h-6') {{ __('Add photo') }}
                    <input type="file" accept="image/*" multiple class="sr-only" @change="add($event)">
                </label>
            </div>
            <input type="file" name="photos[]" multiple x-ref="real" class="hidden">
            @error('photos')<p class="error">{{ $message }}</p>@enderror
            @error('photos.*')<p class="error">{{ $message }}</p>@enderror
        </section>

        {{-- 4. Location & contact --}}
        <section class="card p-4 sm:p-6 space-y-4">
            <h2 class="font-extrabold flex items-center gap-2"><span class="w-6 h-6 rounded-full bg-brand text-white text-xs flex items-center justify-center">4</span> {{ __('Location & contact') }}</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="label" for="district">{{ __('District') }}</label>
                    <select id="district" name="district" x-model="district" required class="input">
                        <option value="">{{ __('Select district') }}</option>
                        @foreach (array_keys(config('marketplace.districts')) as $d)<option value="{{ $d }}">{{ __($d) }}</option>@endforeach
                    </select>
                    @error('district')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="city">{{ __('City / area') }}</label>
                    <input id="city" name="city" x-model="city" list="city-list" maxlength="60" class="input" placeholder="{{ __('e.g. Nugegoda') }}">
                    <datalist id="city-list"><template x-for="c in cityList" :key="c"><option :value="c"></option></template></datalist>
                </div>
                <div>
                    <label class="label" for="contact_name">{{ __('Your name') }}</label>
                    <input id="contact_name" name="contact_name" value="{{ old('contact_name', $listing->contact_name) }}" required maxlength="80" class="input">
                </div>
                <div>
                    <label class="label" for="phone">{{ __('Phone number') }}</label>
                    <input id="phone" name="phone" value="{{ old('phone', $listing->phone) }}" required inputmode="tel" placeholder="0771234567" class="input">
                    @error('phone')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="whatsapp">{{ __('WhatsApp (optional)') }}</label>
                    <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $listing->whatsapp) }}" inputmode="tel" placeholder="{{ __('Same as phone if empty') }}" class="input">
                    @error('whatsapp')<p class="error">{{ $message }}</p>@enderror
                </div>
            </div>
            @if (auth()->user()->stores()->exists())
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="in_store" value="1" @checked(old('in_store', $editing ? (bool) $listing->store_id : true)) class="accent-brand w-4 h-4"> {{ __('Show this ad in my store') }}</label>
            @endif
        </section>

        <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
            <button class="btn-primary py-3.5 px-8 text-base" @disabled($limitReached)>{{ $editing ? __('Save changes') : __('Post ad') }}</button>
            <p class="text-xs text-muted">{!! __('By posting you agree to our :terms. Ads stay live for :days days.', ['terms' => '<a href="'.e(route('pages.terms')).'" class="underline">'.e(__('terms')).'</a>', 'days' => config('marketplace.ad_lifetime_days')]) !!}</p>
        </div>
    </form>
</div>
</x-layouts.app>
