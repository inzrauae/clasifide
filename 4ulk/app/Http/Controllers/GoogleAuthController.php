<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Lang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/** "Continue with Google": signs in, links an existing account by email, or creates a new one. */
class GoogleAuthController extends Controller
{
    public static function enabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function redirect(Request $request)
    {
        abort_unless(self::enabled(), 404);

        // The callback URL has no /si prefix (it must match Google's settings exactly), so remember the language
        $request->session()->put('google_login_lang', app()->getLocale());

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        abort_unless(self::enabled(), 404);

        $prefix = $request->session()->pull('google_login_lang') === 'si' ? '/si' : '';
        $login = $prefix.'/login';

        if ($request->filled('error')) {
            // The person pressed "Cancel" on Google's screen
            return redirect($login);
        }

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            Log::warning('Google sign-in failed: '.$e->getMessage());

            return redirect($login)->withErrors(['login' => self::t('Google sign-in did not work. Please try again.', $prefix)]);
        }

        $email = Str::lower((string) $google->getEmail());
        $emailVerified = (bool) ($google->user['email_verified'] ?? $google->user['verified_email'] ?? false);

        $user = User::where('google_id', $google->getId())->first();

        // Same email as an existing account: link it, but only if Google has confirmed the address
        if (! $user && $email !== '') {
            $existing = User::where('email', $email)->first();
            if ($existing && ! $emailVerified) {
                return redirect($login)->withErrors(['login' => self::t('An account already uses this email. Sign in with your password.', $prefix)]);
            }
            $user = $existing;
        }

        $isNew = false;
        if (! $user) {
            if ($email === '') {
                return redirect($login)->withErrors(['login' => self::t('Your Google account did not share an email address.', $prefix)]);
            }
            $user = User::create([
                'name' => Str::limit(trim((string) ($google->getName() ?: Str::before($email, '@'))), 80, ''),
                'email' => $email,
                'password' => Str::random(40), // hashed by the model; they can set one later with "Forgot password"
                'account_type' => 'individual',
            ]);
            $isNew = true;
        }

        if ($user->isBanned()) {
            return redirect($login)->withErrors(['login' => self::t('This account has been suspended. Contact support if you think this is a mistake.', $prefix)]);
        }

        $user->forceFill(array_filter([
            'google_id' => $user->google_id ?: $google->getId(),
            'email_verified_at' => $user->email_verified_at ?? ($emailVerified ? now() : null),
        ]))->save();

        Auth::login($user, true);
        $request->session()->regenerate();

        if ($isNew) {
            return redirect($prefix.'/dashboard')->with('status', self::t('Welcome to 4U.lk! Add your mobile number in your profile so buyers can call you.', $prefix));
        }

        return redirect()->intended($prefix.'/dashboard');
    }

    /** Translate for the language the person started in (this request itself is unprefixed, so English). */
    private static function t(string $text, string $prefix): string
    {
        return __($text, [], $prefix === '/si' ? 'si' : Lang::DEFAULT);
    }
}
