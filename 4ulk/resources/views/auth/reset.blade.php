<x-auth-card title="Choose a new password">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label class="label" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required class="input">
            @error('email')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="password">New password</label>
            <input id="password" type="password" name="password" required minlength="8" class="input" autocomplete="new-password">
            @error('password')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required class="input" autocomplete="new-password">
        </div>
        <button class="btn-primary w-full py-3">Save password</button>
    </form>
</x-auth-card>
