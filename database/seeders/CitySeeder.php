<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            'US' => [
                ['name' => 'New York',    'lat' => 40.7128,  'lng' => -74.0060],
                ['name' => 'Los Angeles', 'lat' => 34.0522,  'lng' => -118.2437],
                ['name' => 'Chicago',     'lat' => 41.8781,  'lng' => -87.6298],
                ['name' => 'Houston',     'lat' => 29.7604,  'lng' => -95.3698],
            ],
            'FR' => [
                ['name' => 'Paris',       'lat' => 48.8566,  'lng' => 2.3522],
                ['name' => 'Lyon',        'lat' => 45.7640,  'lng' => 4.8357],
                ['name' => 'Marseille',   'lat' => 43.2965,  'lng' => 5.3698],
                ['name' => 'Toulouse',    'lat' => 43.6047,  'lng' => 1.4442],
            ],
            'DE' => [
                ['name' => 'Berlin',      'lat' => 52.5200,  'lng' => 13.4050],
                ['name' => 'Munich',      'lat' => 48.1351,  'lng' => 11.5820],
                ['name' => 'Hamburg',     'lat' => 53.5753,  'lng' => 10.0153],
                ['name' => 'Frankfurt',   'lat' => 50.1109,  'lng' => 8.6821],
            ],
            'MA' => [
                ['name' => 'Casablanca',  'lat' => 33.5731,  'lng' => -7.5898],
                ['name' => 'Rabat',       'lat' => 34.0209,  'lng' => -6.8416],
                ['name' => 'Marrakech',   'lat' => 31.6295,  'lng' => -7.9811],
                ['name' => 'Fes',         'lat' => 34.0331,  'lng' => -5.0003],
            ],
        ];

        foreach ($cities as $isoCode => $citiesData) {
            $country = Country::where('iso_code', $isoCode)->first();
            foreach ($citiesData as $cityData) {
                City::create([...$cityData, 'country_id' => $country->id]);
            }
        }
    }
}