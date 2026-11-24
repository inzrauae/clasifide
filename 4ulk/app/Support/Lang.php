<?php

namespace App\Support;

use Illuminate\Support\Facades\App;

/** Site languages: English at the root, Sinhala under /si. */
class Lang
{
    public const DEFAULT = 'en';

    /** Languages served under their own URL prefix. */
    public const PREFIXED = ['si'];

    public const NAMES = ['en' => 'English', 'si' => 'සිංහල'];

    public static function isSinhala(): bool
    {
        return App::getLocale() === 'si';
    }

    /** Messages shown by resources/js/app.js, translated for the page's language. */
    public static function jsStrings(): array
    {
        return collect([
            'Could not load the number. Try again.',
            'Saved to your favourites',
            'Removed from favourites',
            'Link copied',
            'Location is not available on this device',
            'Allow location access to find ads near you',
            'Showing ads near you in :place',
        ])->mapWithKeys(fn ($s) => [$s => __($s)])->all();
    }

    /**
     * The current page in every language, for the language switch and hreflang tags:
     * ['en' => 'https://4u.lk/ads?q=car', 'si' => 'https://4u.lk/si/ads?q=car'].
     */
    public static function alternates(): array
    {
        $request = request();
        $root = $request->getSchemeAndHttpHost().$request->getBaseUrl();
        $path = $request->getPathInfo(); // already without the /si prefix
        $query = $request->getQueryString();
        $tail = ($query !== null ? '?'.$query : '');

        $urls = ['en' => $root.($path === '/' ? '/' : $path).$tail];
        foreach (self::PREFIXED as $locale) {
            $urls[$locale] = $root.'/'.$locale.($path === '/' ? '' : $path).$tail;
        }

        return $urls;
    }
}
