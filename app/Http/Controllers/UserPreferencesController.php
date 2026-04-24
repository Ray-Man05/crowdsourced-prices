<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class UserPreferencesController extends Controller
{
    public function switch_locale(Request $request, string $locale): RedirectResponse
    {
        if (!in_array($locale, ['en', 'fr'])) {
            abort(400);
        }

        $request->session()->put('locale', $locale);

        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
        }

        return back();
    }

    public function switch_theme(Request $request, string $theme): RedirectResponse
    {
        if (!in_array($theme, ['light', 'dark'])) {
            abort(400);
        }

        if ($request->user()) {
            $request->user()->update(['theme' => $theme]);
        } else {
            $request->session()->put('theme', $theme);
        }

        return back();
    }
}