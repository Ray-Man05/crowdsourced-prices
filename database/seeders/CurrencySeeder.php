<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Services\RestCountriesService;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function __construct(private RestCountriesService $api) {}

    public function run(): void
    {
        foreach ($this->api->getUniqueCurrencies() as $currency) {
            Currency::create($currency);
        }
    }
}
