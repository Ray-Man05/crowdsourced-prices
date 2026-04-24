<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        $usd = Currency::where('code', 'USD')->first();
        $eur = Currency::where('code', 'EUR')->first();
        $mad = Currency::where('code', 'MAD')->first();

        // setRate() automatically creates both directions
        ExchangeRate::setRate($usd, $eur, 0.92);
        ExchangeRate::setRate($usd, $mad, 10.05);
        ExchangeRate::setRate($eur, $mad, 10.93);
    }
}