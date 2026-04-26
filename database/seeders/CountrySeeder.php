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
        $dzd = Currency::where('code', 'DZD')->first();
        $inr = Currency::where('code', 'INR')->first();
        $cny = Currency::where('code', 'CNY')->first();
        $zar = Currency::where('code', 'ZAR')->first();
        $egp = Currency::where('code', 'EGP')->first();

        $countries = [
            ['name' => 'United States', 'iso_code' => 'US', 'currency_id' => $usd->id],

            ['name' => 'France',        'iso_code' => 'FR', 'currency_id' => $eur->id],
            ['name' => 'Germany',       'iso_code' => 'DE', 'currency_id' => $eur->id],
            ['name' => 'Austria',       'iso_code' => 'AT', 'currency_id' => $eur->id],
            ['name' => 'Spain',       'iso_code' => 'ES', 'currency_id' => $eur->id],
            ['name' => 'Portugal',       'iso_code' => 'PT', 'currency_id' => $eur->id],
            ['name' => 'Italy',       'iso_code' => 'IT', 'currency_id' => $eur->id],
            ['name' => 'Greece',       'iso_code' => 'GR', 'currency_id' => $eur->id],
            ['name' => 'Netherlands',       'iso_code' => 'NL', 'currency_id' => $eur->id],
            ['name' => 'Belgium',       'iso_code' => 'BE', 'currency_id' => $eur->id],
            ['name' => 'Bulgaria',       'iso_code' => 'BG', 'currency_id' => $eur->id],

            ['name' => 'Morocco',       'iso_code' => 'MA', 'currency_id' => $mad->id],
            ['name' => 'Algeria',       'iso_code' => 'DZ', 'currency_id' => $dzd->id],
            ['name' => 'China',       'iso_code' => 'CN', 'currency_id' => $cny->id],
            ['name' => 'India',       'iso_code' => 'IN', 'currency_id' => $inr->id],
            ['name' => 'South Africa',       'iso_code' => 'ZA', 'currency_id' => $zar->id],
            ['name' => 'Egypt',       'iso_code' => 'EG', 'currency_id' => $egp->id],
        ];

        foreach ($countries as $country) {
            Country::create($country);
        }
    }
}