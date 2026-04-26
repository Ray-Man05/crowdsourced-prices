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
            ['name' => 'Moroccan Dirham', 'code' => 'MAD', 'symbol' => 'DH'],
            
            ['name' => 'Algerian Dinar', 'code' => 'DZD', 'symbol' => 'DA'],
            

            ['name' => 'Renminbi', 'code' => 'CNY', 'symbol' => '¥'],
            ['name' => 'Indian Rupee', 'code' => 'INR', 'symbol' => '₹'],


            ['name' => 'Egyptian Pound', 'code' => 'EGP', 'symbol' => 'LE'],
            ['name' => 'South African Rand', 'code' => 'ZAR', 'symbol' => 'R'],
        ];

        foreach ($currencies as $currency) {
            Currency::create($currency);
        }
    }
}