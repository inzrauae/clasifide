<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function safety()
    {
        return view('pages.safety');
    }

    public function about()
    {
        return view('pages.about');
    }

    public function terms()
    {
        return view('pages.terms');
    }

    public function privacy()
    {
        return view('pages.privacy');
    }
}
