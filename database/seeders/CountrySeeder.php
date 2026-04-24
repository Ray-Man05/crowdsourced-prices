<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $usd = Currency::where('code', 'USD')->first();
        $eur = Currency::where('code', 'EUR')->first();
        $mad = Currency::where('code', 'MAD')->first();

        $countries = [
            ['name' => 'United States', 'iso_code' => 'US', 'currency_id' => $usd->id],
            ['name' => 'France',        'iso_code' => 'FR', 'currency_id' => $eur->id],
            ['name' => 'Germany',       'iso_code' => 'DE', 'currency_id' => $eur->id],
            ['name' => 'Morocco',       'iso_code' => 'MA', 'currency_id' => $mad->id],
        ];

        foreach ($countries as $country) {
            Country::create($country);
        }
    }
}