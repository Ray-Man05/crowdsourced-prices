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
        $dzd = Currency::where('code', 'DZD')->first();

        $cny = Currency::where('code', 'CNY')->first();
        $inr = Currency::where('code', 'inr')->first();
        $egp = Currency::where('code', 'egp')->first();
        $zar = Currency::where('code', 'zar')->first();

        // setRate() automatically creates both directions
        ExchangeRate::setRate($usd, $eur, 0.85);
        ExchangeRate::setRate($usd, $mad, 9.24);
        ExchangeRate::setRate($usd, $cny, 6.84);
        ExchangeRate::setRate($usd, $inr, 94.28);
        ExchangeRate::setRate($usd, $egp, 52.62);
        ExchangeRate::setRate($usd, $zar, 16.55);


        ExchangeRate::setRate($eur, $mad, 10.84);
        ExchangeRate::setRate($eur, $cny, 8.01);
        ExchangeRate::setRate($eur, $inr, 110.32);
        ExchangeRate::setRate($eur, $egp, 61.53);
        ExchangeRate::setRate($eur, $zar, 19.39);

        ExchangeRate::setRate($mad, $cny, 0.74);
        ExchangeRate::setRate($mad, $inr, 10.18);
        ExchangeRate::setRate($mad, $egp, 5.61);
        ExchangeRate::setRate($mad, $zar, 1.78);

        ExchangeRate::setRate($cny, $inr, 13.77);
        ExchangeRate::setRate($cny, $egp, 7.69);
        ExchangeRate::setRate($cny, $zar, 2.42);

        ExchangeRate::setRate($inr, $egp, 0.56);
        ExchangeRate::setRate($inr, $zar, 0.18);
     
        ExchangeRate::setRate($egp, $zar, 0.32);

        ExchangeRate::setRate($dzd, $usd, 0.007535);
        ExchangeRate::setRate($dzd, $eur, 0.006451);
        ExchangeRate::setRate($dzd, $mad, 0.069867);
        ExchangeRate::setRate($dzd, $cny, 0.051591);
        ExchangeRate::setRate($dzd, $inr, 0.709792);
        ExchangeRate::setRate($dzd, $egp, 0.391603);
        ExchangeRate::setRate($dzd, $zar, 0.124512);
    }
}