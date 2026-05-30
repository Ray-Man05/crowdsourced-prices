<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\PriceEstimate;
use App\Services\PriceAggregator;

class LandingController extends Controller
{
    public function show(PriceAggregator $aggregator)
    {
        $cities = $aggregator->coverageByCity(0);

        $stats = [
            'cities'    => $cities->count(),
            'estimates' => PriceEstimate::count(),
            'countries' => $cities->pluck('country')->unique()->count(),
        ];

        return view('landing', compact('cities', 'stats'));
    }
}
