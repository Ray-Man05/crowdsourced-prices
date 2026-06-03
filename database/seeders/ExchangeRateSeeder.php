<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        if ($this->command) {
            $this->command->call('exchange-rates:update');
        } else {
            Artisan::call('exchange-rates:update');
        }
    }
}
