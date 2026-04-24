<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['name' => 'US Dollar',       'code' => 'USD', 'symbol' => '$'],
            ['name' => 'Euro',            'code' => 'EUR', 'symbol' => '€'],
            ['name' => 'Moroccan Dirham', 'code' => 'MAD', 'symbol' => 'د.م.'],
        ];

        foreach ($currencies as $currency) {
            Currency::create($currency);
        }
    }
}