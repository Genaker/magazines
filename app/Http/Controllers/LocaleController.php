<?php

namespace App\Http\Controllers;

use App\Support\SiteLocale;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function __invoke(string $locale): RedirectResponse
    {
        $locale = SiteLocale::normalize($locale);

        if (! SiteLocale::isEnabled($locale)) {
            abort(404);
        }

        session(['locale' => $locale]);

        return redirect()->back();
    }
}
