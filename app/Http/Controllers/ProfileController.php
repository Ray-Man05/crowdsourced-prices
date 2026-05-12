<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Country;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $currencies = Currency::orderBy('name')->get();

        $countryCurrencies = Country::whereNotNull('currency_id')
            ->pluck('currency_id', 'id');

        return view('profile.edit', [
            'user'               => $request->user(),
            'currencies'         => $currencies,
            'countryCurrencies'  => $countryCurrencies,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's location and currency (subject to a weekly cooldown).
     */
    public function updateLocation(Request $request): RedirectResponse
    {
        $request->validate([
            'city_id'     => ['nullable', 'exists:cities,id'],
            'currency_id' => ['nullable', 'exists:currencies,id'],
        ]);

        $user = $request->user();

        if (!$user->canUpdateLocation()) {
            $endsAt = $user->locationCooldownEndsAt()->translatedFormat('M j, Y');
            return Redirect::route('profile.edit')
                ->with('status', 'location-on-cooldown')
                ->with('cooldown-ends-at', $endsAt);
        }

        $user->city_id             = $request->city_id ?: null;
        $user->currency_id         = $request->currency_id ?: null;
        $user->location_updated_at = now();
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'location-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
