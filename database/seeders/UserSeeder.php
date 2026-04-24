<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $cities      = City::all();
        $currencies  = Currency::all();
        $locales     = ['en', 'fr'];
        $themes      = ['light', 'dark'];

        // Admin account — fixed, easy to find in tests
        User::create([
            'name'        => 'Admin',
            'email'       => 'admin@example.com',
            'password'    => Hash::make('password'),
            'role'        => 'admin',
            'locale'      => 'en',
            'theme'       => 'light',
            'city_id'     => $cities->first()->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
        ]);

        // 9 regular users
        $names = [
            'Simon Bolivar', 'Ogedei Khan', 'Marcus Tullius Cicero',
            'Gamal Abdel Nasser', 'Frederick Douglass', 'Pedro II',
            'Sinn Sisamouth', 'Gilgamesh', 'Haile Selassie',
        ];

        foreach ($names as $name) {
            $email = strtolower(str_replace(' ', '.', $name)) . '@example.com';

            User::create([
                'name'        => $name,
                'email'       => $email,
                'password'    => Hash::make('password'),
                'role'        => 'user',
                'locale'      => $locales[array_rand($locales)],
                'theme'       => $themes[array_rand($themes)],
                'city_id'     => $cities->random()->id,
                'currency_id' => $currencies->random()->id,
            ]);
        }
    }
}