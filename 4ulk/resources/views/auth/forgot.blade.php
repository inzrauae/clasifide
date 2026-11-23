<x-auth-card title="Reset your password" subtitle="We will email you a link to choose a new password.">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <div>
            <label class="label" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="input">
            @error('email')<p class="error">{{ $message }}</p>@enderror
        </div>
        <button class="btn-primary w-full py-3">Send reset link</button>
        <p class="text-center text-sm"><a href="{{ route('login') }}" class="font-semibold text-brand-dark hover:underline">Back to sign in</a></p>
    </form>
</x-auth-card>
