<?php

namespace App\Support;

/** IDs of ads the signed-in user has saved, loaded once per request. */
class Saved
{
    private static ?array $ids = null;

    public static function ids(): array
    {
        if (self::$ids === null) {
            self::$ids = auth()->check() ? auth()->user()->favorites()->pluck('listings.id')->all() : [];
        }

        return self::$ids;
    }

    public static function has(int $id): bool
    {
        return in_array($id, self::ids(), true);
    }
}
