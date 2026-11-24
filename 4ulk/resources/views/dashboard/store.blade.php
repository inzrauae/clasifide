<x-dashboard-shell title="{{ __('My store') }}" active="store">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-extrabold">{{ __('My store') }}</h1>
        @if ($store->exists)<a href="{{ route('stores.show', $store) }}" target="_blank" class="btn-ghost !py-2">{{ __('View store page') }}</a>@endif
    </div>

    @unless ($allowed)
        <div class="mt-4 rounded-2xl bg-night text-white p-6 text-center">
            <span class="mx-auto w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center">@svg('lucide-store', 'w-7 h-7 text-sun')</span>
            <p class="mt-3 text-xl font-extrabold">{{ __('Open your online store on 4U.lk') }}</p>
            <p class="mt-1 text-sm text-white/70 max-w-md mx-auto">{{ __('Get your own page at 4u.lk/store/your-name with your logo, banner, opening hours and all your ads in one place.') }}</p>
            <a href="{{ route('pricing') }}" class="btn-sun mt-4">{{ __('Start with Starter – Rs 990/month') }}</a>
        </div>
    @else
        <form method="POST" action="{{ route('dashboard.store.save') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <div class="card overflow-hidden">
                <div class="relative h-36 sm:h-44 hero-band">
                    @if ($store->banner)<img src="{{ asset($store->banner) }}" class="absolute inset-0 w-full h-full object-cover" alt="">@endif
                    <label class="absolute bottom-3 right-3 btn !py-1.5 bg-white/90 text-ink cursor-pointer text-xs">@svg('lucide-upload', 'w-3.5 h-3.5') {{ __('Banner (1600×400)') }}<input type="file" name="banner" accept="image/*" class="sr-only"></label>
                </div>
                <div class="p-5 -mt-12 relative flex items-end gap-3">
                    <span class="w-20 h-20 rounded-2xl ring-4 ring-white brand-gradient text-white text-xl font-bold flex items-center justify-center overflow-hidden">
                        @if ($store->logo)<img src="{{ asset($store->logo) }}" class="w-full h-full object-cover" alt="">@else{{ $store->exists ? $store->initials() : 'LOGO' }}@endif
                    </span>
                    <label class="btn-ghost !py-1.5 text-xs cursor-pointer">@svg('lucide-upload', 'w-3.5 h-3.5') {{ __('Logo') }}<input type="file" name="logo" accept="image/*" class="sr-only"></label>
                </div>
            </div>
            <div class="card p-5 grid sm:grid-cols-2 gap-4">
                <div><label class="label" for="name">{{ __('Store name') }}</label><input id="name" name="name" value="{{ old('name', $store->name) }}" required class="input">@error('name')<p class="error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="slug">{{ __('Store address') }}</label>
                    <div class="flex items-center input !p-0 overflow-hidden"><span class="pl-3 text-faint text-sm whitespace-nowrap">{{ __('4u.lk/store/') }}</span><input id="slug" name="slug" value="{{ old('slug', $store->slug) }}" class="w-full py-2.5 pr-3 focus:outline-none bg-transparent" placeholder="{{ __('your-shop') }}"></div>
                    @error('slug')<p class="error">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><label class="label" for="tagline">{{ __('Tagline') }}</label><input id="tagline" name="tagline" value="{{ old('tagline', $store->tagline) }}" maxlength="120" class="input" placeholder="{{ __('e.g. Reconditioned Japanese cars since 2005') }}"></div>
                <div class="sm:col-span-2"><label class="label" for="description">{{ __('About your business') }}</label><textarea id="description" name="description" rows="4" class="input">{{ old('description', $store->description) }}</textarea></div>
                <div><label class="label" for="phone">{{ __('Phone') }}</label><input id="phone" name="phone" value="{{ old('phone', $store->phone) }}" class="input">@error('phone')<p class="error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="whatsapp">{{ __('WhatsApp') }}</label><input id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $store->whatsapp) }}" class="input">@error('whatsapp')<p class="error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="email">{{ __('Email') }}</label><input id="email" type="email" name="email" value="{{ old('email', $store->email) }}" class="input"></div>
                <div><label class="label" for="website">{{ __('Website') }}</label><input id="website" name="website" value="{{ old('website', $store->website) }}" class="input" placeholder="{{ __('https://') }}">@error('website')<p class="error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="district">{{ __('District') }}</label><select id="district" name="district" class="input"><option value="">{{ __('Select') }}</option>@foreach ($districts as $d)<option value="{{ $d }}" @selected(old('district', $store->district) === $d)>{{ __($d) }}</option>@endforeach</select></div>
                <div><label class="label" for="city">{{ __('City') }}</label><input id="city" name="city" value="{{ old('city', $store->city) }}" class="input"></div>
                <div><label class="label" for="address">{{ __('Address') }}</label><input id="address" name="address" value="{{ old('address', $store->address) }}" class="input"></div>
                <div><label class="label" for="opening_hours">{{ __('Opening hours') }}</label><input id="opening_hours" name="opening_hours" value="{{ old('opening_hours', $store->opening_hours) }}" class="input" placeholder="{{ __('Mon–Sat, 9am–6pm') }}"></div>
            </div>
            <button class="btn-primary px-8 py-3">{{ $store->exists ? __('Save store') : __('Open my store') }}</button>
        </form>
    @endunless
</x-dashboard-shell>
