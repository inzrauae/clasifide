<?php

namespace App\Http;

use Illuminate\Http\Request;

/**
 * A request that arrived under a language prefix (/si/...).
 *
 * Routing sees the path without the prefix (getPathInfo() is "/ads"), but the
 * request's own URL keeps it (url()/fullUrl() are ".../si/ads"). That way
 * "back" redirects, the post-login "intended" page and the session's previous
 * URL all keep the visitor in their language.
 */
class LocalizedRequest extends Request
{
    public string $localePrefix = '';

    public function getUri(): string
    {
        $query = $this->getQueryString();

        return $this->getSchemeAndHttpHost().$this->getBaseUrl().$this->localePrefix.$this->getPathInfo()
            .($query !== null ? '?'.$query : '');
    }
}
