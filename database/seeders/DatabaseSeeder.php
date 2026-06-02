<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CurrencySeeder::class,
            ExchangeRateSeeder::class,
            CountrySeeder::class,
            CitySeeder::class,
            UnitSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            UserSeeder::class,
            PriceEstimateSeeder::class,
        ]);
    }
}
