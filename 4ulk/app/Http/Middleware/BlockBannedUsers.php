<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockBannedUsers
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user?->isBanned()) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['email' => __('This account has been suspended. Contact support if you think this is a mistake.')]);
        }

        // Cheap "last seen" for seller profiles (at most once every 5 minutes)
        if ($user && (! $user->last_seen_at || $user->last_seen_at->lt(now()->subMinutes(5)))) {
            $user->forceFill(['last_seen_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
