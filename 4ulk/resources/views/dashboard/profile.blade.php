<x-dashboard-shell title="Profile" active="profile">
    <h1 class="text-2xl font-extrabold">Profile</h1>
    <form method="POST" action="{{ route('dashboard.profile.update') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
        @csrf @method('PUT')
        <div class="card p-5 grid sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2 flex items-center gap-4">
                <span class="w-16 h-16 rounded-full brand-gradient text-white text-lg font-bold flex items-center justify-center overflow-hidden">
                    @if ($user->avatar)<img src="{{ asset($user->avatar) }}" class="w-full h-full object-cover" alt="">@else{{ $user->initials() }}@endif
                </span>
                <label class="btn-ghost cursor-pointer">@svg('lucide-camera', 'w-4 h-4') Change photo<input type="file" name="avatar" accept="image/*" class="sr-only"></label>
            </div>
            <div><label class="label" for="name">Name</label><input id="name" name="name" value="{{ old('name', $user->name) }}" required class="input">@error('name')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="input">@error('email')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="phone">Mobile</label><input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" required class="input">@error('phone')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="whatsapp">WhatsApp</label><input id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $user->whatsapp) }}" class="input">@error('whatsapp')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="district">District</label>
                <select id="district" name="district" class="input"><option value="">Select</option>@foreach ($districts as $d)<option @selected(old('district', $user->district) === $d)>{{ $d }}</option>@endforeach</select></div>
            <div><label class="label" for="city">City</label><input id="city" name="city" value="{{ old('city', $user->city) }}" class="input"></div>
        </div>

        <div class="card p-5 grid sm:grid-cols-3 gap-4">
            <h2 class="sm:col-span-3 font-extrabold">Change password</h2>
            <div><label class="label" for="current_password">Current password</label><input id="current_password" type="password" name="current_password" class="input" autocomplete="current-password">@error('current_password')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="password">New password</label><input id="password" type="password" name="password" class="input" autocomplete="new-password">@error('password')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="password_confirmation">Confirm</label><input id="password_confirmation" type="password" name="password_confirmation" class="input" autocomplete="new-password"></div>
        </div>

        <div class="card p-5 flex items-start gap-3">
            @svg('lucide-shield-check', 'w-6 h-6 '.($user->isVerified() ? 'text-emerald-600' : 'text-faint'))
            <div class="text-sm">
                @if ($user->isVerified())
                    <p class="font-bold text-emerald-700">You are a verified seller</p><p class="text-muted">Buyers see a VERIFIED badge on your ads and store.</p>
                @else
                    <p class="font-bold">Get verified</p><p class="text-muted">Businesses on a paid package can request verification. Email your BR certificate or NIC to <a href="mailto:{{ config('marketplace.support_email') }}" class="underline">{{ config('marketplace.support_email') }}</a> and our team will add the badge.</p>
                @endif
            </div>
        </div>
        <button class="btn-primary px-8 py-3">Save profile</button>
    </form>
</x-dashboard-shell>
