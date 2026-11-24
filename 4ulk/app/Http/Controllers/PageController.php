<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;

class PageController extends Controller
{
    public function safety()
    {
        return $this->page('safety');
    }

    public function about()
    {
        return $this->page('about');
    }

    public function terms()
    {
        return $this->page('terms');
    }

    public function privacy()
    {
        return $this->page('privacy');
    }

    /** Long-form pages have a hand-written version per language (resources/views/pages/si/...). */
    private function page(string $name)
    {
        $localized = 'pages.'.App::getLocale().'.'.$name;

        return view(view()->exists($localized) ? $localized : 'pages.'.$name);
    }
}
