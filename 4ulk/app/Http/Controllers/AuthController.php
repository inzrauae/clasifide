<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Sign in with email or mobile number
        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $value = $field === 'phone' ? self::normalisePhone($data['login']) : $data['login'];

        if (! Auth::attempt([$field => $value, 'password' => $data['password']], $request->boolean('remember'))) {
            return back()->withErrors(['login' => 'Those details do not match an account.'])->onlyInput('login');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister()
    {
        return view('auth.register', ['districts' => array_keys(config('marketplace.districts'))]);
    }

    public function register(Request $request)
    {
        // Honeypot: bots fill every field
        abort_if($request->filled('website'), 422);

        $request->merge(['phone' => self::normalisePhone((string) $request->input('phone'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'phone' => ['required', 'regex:/^0\d{9}$/', 'unique:users,phone'],
            'district' => ['required', Rule::in(array_keys(config('marketplace.districts')))],
            'account_type' => ['required', Rule::in(['individual', 'business'])],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
            'terms' => ['accepted'],
        ], [
            'phone.regex' => 'Enter a Sri Lankan mobile number like 0771234567.',
            'phone.unique' => 'An account already uses this mobile number.',
        ]);

        $user = User::create($data + ['whatsapp' => $data['phone']]);
        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Welcome to 4U.lk! Post your first ad for free.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function showForgot()
    {
        return view('auth.forgot');
    }

    public function sendReset(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        // Same answer whether or not the email exists
        return back()->with('status', 'If that email has an account, a reset link is on its way.');
    }

    public function showReset(Request $request, string $token)
    {
        return view('auth.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Password changed. You can sign in now.')
            : back()->withErrors(['email' => __($status)]);
    }

    /** 077 123 4567 / +94771234567 / 94771234567 -> 0771234567 */
    public static function normalisePhone(string $phone): string
    {
        $d = preg_replace('/\D/', '', $phone);
        if (str_starts_with($d, '94') && strlen($d) === 11) {
            $d = '0'.substr($d, 2);
        } elseif (strlen($d) === 9 && $d[0] !== '0') {
            $d = '0'.$d;
        }

        return $d;
    }
}
