<x-auth-card title="Sign in" subtitle="Welcome back to 4U.lk">
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="label" for="login">Email or mobile number</label>
            <input id="login" name="login" value="{{ old('login') }}" required autofocus autocomplete="username" class="input">
            @error('login')<p class="error">{{ $message }}</p>@enderror
            @error('email')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <div class="flex justify-between"><label class="label" for="password">Password</label><a href="{{ route('password.request') }}" class="text-xs font-semibold text-brand-dark hover:underline">Forgot?</a></div>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="input">
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1" checked class="accent-brand w-4 h-4"> Keep me signed in</label>
        <button class="btn-primary w-full py-3">Sign in</button>
        <p class="text-center text-sm text-muted">New to 4U.lk? <a href="{{ route('register') }}" class="font-bold text-brand-dark hover:underline">Create a free account</a></p>
    </form>
</x-auth-card>
