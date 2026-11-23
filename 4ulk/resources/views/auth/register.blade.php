<x-auth-card title="Create your account" subtitle="Free forever for personal sellers">
    <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ type: '{{ old('account_type', 'individual') }}' }">
        @csrf
        <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
        <div class="grid grid-cols-2 gap-2">
            @foreach (['individual' => ['lucide-circle-user', 'Individual'], 'business' => ['lucide-store', 'Business']] as $k => [$icon, $label])
                <label class="cursor-pointer">
                    <input type="radio" name="account_type" value="{{ $k }}" x-model="type" class="peer sr-only">
                    <span class="flex items-center justify-center gap-2 rounded-xl border border-line py-3 text-sm font-bold peer-checked:border-brand peer-checked:bg-mist peer-checked:text-brand-dark">@svg($icon, 'w-4 h-4') {{ $label }}</span>
                </label>
            @endforeach
        </div>
        <p x-show="type === 'business'" x-cloak class="text-xs text-muted -mt-2">Business accounts can open a store and get verified. See <a href="{{ route('pricing') }}" class="underline">packages</a>.</p>
        <div>
            <label class="label" for="name"><span x-text="type === 'business' ? 'Business or contact name' : 'Full name'">Full name</span></label>
            <input id="name" name="name" value="{{ old('name') }}" required maxlength="80" class="input" autocomplete="name">
            @error('name')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required class="input" autocomplete="email">
            @error('email')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="label" for="phone">Mobile number</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" required inputmode="tel" placeholder="0771234567" class="input" autocomplete="tel">
                @error('phone')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="district">District</label>
                <select id="district" name="district" required class="input">
                    <option value="">Select</option>
                    @foreach ($districts as $d)<option @selected(old('district') === $d)>{{ $d }}</option>@endforeach
                </select>
                @error('district')<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="label" for="password">Password</label>
            <input id="password" type="password" name="password" required minlength="8" class="input" autocomplete="new-password">
            @error('password')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required class="input" autocomplete="new-password">
        </div>
        <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="terms" value="1" required class="accent-brand w-4 h-4 mt-0.5"> <span>I agree to the <a href="{{ route('pages.terms') }}" class="underline">terms</a> and <a href="{{ route('pages.privacy') }}" class="underline">privacy policy</a>.</span></label>
        @error('terms')<p class="error">{{ $message }}</p>@enderror
        <button class="btn-primary w-full py-3">Create account</button>
        <p class="text-center text-sm text-muted">Already have an account? <a href="{{ route('login') }}" class="font-bold text-brand-dark hover:underline">Sign in</a></p>
    </form>
</x-auth-card>
