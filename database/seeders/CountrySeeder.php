<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Currency;
use App\Services\RestCountriesService;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function __construct(private RestCountriesService $api) {}

    public function run(): void
    {
        $currencyIndex = Currency::all()->keyBy('code');

        foreach ($this->api->getCountries() as $country) {
            $currency = $currencyIndex[$country['currency_code']] ?? null;

            if (! $currency) {
                continue;
            }

            Country::create([
                'name'        => $country['name'],
                'iso_code'    => $country['iso_code'],
                'currency_id' => $currency->id,
            ]);
        }
    }
}