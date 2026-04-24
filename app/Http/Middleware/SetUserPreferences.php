<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetUserPreferences
{
    public function handle(Request $request, Closure $next)
    {
        // --- Locale ---
        $locale = 'en';

        if ($request->user()) {
            $locale = $request->user()->locale;
        } elseif ($request->session()->has('locale')) {
            $locale = $request->session()->get('locale');
        }

        if (in_array($locale, ['en', 'fr'])) {
            App::setLocale($locale);
        }

        // --- Theme ---
        $theme = 'light';

        if ($request->user()) {
            $theme = $request->user()->theme;
        } elseif ($request->session()->has('theme')) {
            $theme = $request->session()->get('theme');
        }

        if (in_array($theme, ['light', 'dark'])) {
            view()->share('userTheme', $theme);
        }

        return $next($request);
    }
}