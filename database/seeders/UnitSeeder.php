<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => ['en' => 'Kilogram', 'fr' => 'Kilogramme'], 'symbol' => 'kg'],
            ['name' => ['en' => 'Gram',     'fr' => 'Gramme'],     'symbol' => 'g'],
            ['name' => ['en' => 'Liter',    'fr' => 'Litre'],      'symbol' => 'L'],
            ['name' => ['en' => 'Unit',     'fr' => 'Unité'],      'symbol' => 'pc'],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }
    }
}
