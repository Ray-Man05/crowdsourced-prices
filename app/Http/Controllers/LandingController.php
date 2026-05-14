<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\PriceEstimate;

class LandingController extends Controller
{
    // Leave empty to show all cities with estimates on the hero map.
    // Populate with specific City IDs to feature only flagship cities.
    private const FEATURED_CITY_IDS = [];

    public function show()
    {
        $query = City::whereHas('priceEstimates');

        if (!empty(self::FEATURED_CITY_IDS)) {
            $query->whereIn('id', self::FEATURED_CITY_IDS);
        }

        $cities = $query->get(['id', 'name', 'lat', 'lng']);

        $stats = [
            'cities'    => City::whereHas('priceEstimates')->count(),
            'estimates' => PriceEstimate::count(),
            'countries' => Country::whereHas('cities.priceEstimates')->count(),
        ];

        return view('landing', compact('cities', 'stats'));
    }
}
