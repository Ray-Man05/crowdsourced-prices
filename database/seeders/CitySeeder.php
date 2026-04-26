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
            'IN' => [
                ['name' => 'Delhi', 'lat' => 28.61, 'lng' => 77.23],
                ['name' => 'Mumbai', 'lat' => 19.0761, 'lng' => 72.8775],
                ['name' => 'Bangalore', 'lat' => 12.9789, 'lng' => 77.5917],
                ['name' => 'Chennai', 'lat' => 13.0825, 'lng' => 80.275],
                ['name' => 'Ahmedabad', 'lat' => 23.0225, 'lng' => 72.5714],
                ['name' => 'Lucknow', 'lat' => 26.85, 'lng' => 80.95],
                ['name' => 'Jaipur', 'lat' => 26.9, 'lng' => 75.8],
            ],
            'CN' => [
                ['name' => 'Guangzhou', 'lat' => 23.13, 'lng' => 113.26],
                ['name' => 'Shanghai', 'lat' => 31.2286, 'lng' => 121.4747],
                ['name' => 'Beijing', 'lat' => 39.9067, 'lng' => 116.3975],
                ['name' => 'Shenzhen', 'lat' => 22.5415, 'lng' => 114.0596],
                ['name' => 'Chengdu', 'lat' => 30.66, 'lng' => 104.0633],
                ['name' => 'Xi\'an', 'lat' => 34.2611, 'lng' => 108.9422],
                ['name' => 'Chongqing', 'lat' => 29.5637, 'lng' => 106.5504],
                ['name' => 'Tianjin', 'lat' => 39.1336, 'lng' => 117.2054],
                ['name' => 'Wuhan', 'lat' => 30.5934, 'lng' => 114.3046],
                ['name' => 'Hangzhou', 'lat' => 30.267, 'lng' => 120.153],
                ['name' => 'Nanjing', 'lat' => 32.0608, 'lng' => 118.7789],
            ],
            'EG' => [
                ['name' => 'Cairo', 'lat' => 30.0444, 'lng' => 31.2358],
                ['name' => 'Giza', 'lat' => 29.987, 'lng' => 31.2118],
                ['name' => 'Alexandria', 'lat' => 31.1975, 'lng' => 29.8925],
                ['name' => 'Port Said', 'lat' => 31.2625, 'lng' => 32.3061],
                ['name' => 'Suez', 'lat' => 29.9667, 'lng' => 32.5333],
                ['name' => 'Aswan', 'lat' => 24.0889, 'lng' => 32.8997],
                ['name' => 'Luxor', 'lat' => 25.6967, 'lng' => 32.6444],
            ],
            'US' => [
                ['name' => 'New York', 'lat' => 40.6943, 'lng' => -73.9249],
                ['name' => 'Los Angeles', 'lat' => 34.1141, 'lng' => -118.4068],
                ['name' => 'Chicago', 'lat' => 41.8375, 'lng' => -87.6866],
                ['name' => 'Miami', 'lat' => 25.784, 'lng' => -80.2101],
                ['name' => 'Houston', 'lat' => 29.786, 'lng' => -95.3885],
                ['name' => 'Dallas', 'lat' => 32.7935, 'lng' => -96.7667],
                ['name' => 'Atlanta', 'lat' => 33.7628, 'lng' => -84.422],
                ['name' => 'Boston', 'lat' => 42.3188, 'lng' => -71.0852],
                ['name' => 'Phoenix', 'lat' => 33.5722, 'lng' => -112.0892],
                ['name' => 'Detroit', 'lat' => 42.3834, 'lng' => -83.1024],
                ['name' => 'Seattle', 'lat' => 47.6211, 'lng' => -122.3244],
                ['name' => 'San Francisco', 'lat' => 37.7558, 'lng' => -122.4449],
                ['name' => 'San Diego', 'lat' => 32.8313, 'lng' => -117.1222],
                ['name' => 'Denver', 'lat' => 39.762, 'lng' => -104.8758],
                ['name' => 'Baltimore', 'lat' => 39.3051, 'lng' => -76.6144],
                ['name' => 'Portland', 'lat' => 45.5371, 'lng' => -122.65],
                ['name' => 'Nashville', 'lat' => 36.1715, 'lng' => -86.7842],
                ['name' => 'Richmond', 'lat' => 37.5295, 'lng' => -77.4756],
                ['name' => 'Memphis', 'lat' => 35.1087, 'lng' => -89.9663],
                ['name' => 'New Orleans', 'lat' => 30.0687, 'lng' => -89.9288],
                ['name' => 'El Paso', 'lat' => 31.8476, 'lng' => -106.43],
                ['name' => 'Fresno', 'lat' => 36.783, 'lng' => -119.7939],
            ],
            'FR' => [
                ['name' => 'Paris', 'lat' => 48.8567, 'lng' => 2.3522],
                ['name' => 'Bordeaux', 'lat' => 44.84, 'lng' => -0.58],
                ['name' => 'Marseille', 'lat' => 43.2964, 'lng' => 5.37],
                ['name' => 'Lyon', 'lat' => 45.76, 'lng' => 4.84],
                ['name' => 'Toulouse', 'lat' => 43.6045, 'lng' => 1.444],
                ['name' => 'Nice', 'lat' => 43.7034, 'lng' => 7.2663],
                ['name' => 'Nantes', 'lat' => 47.2181, 'lng' => -1.5528],
                ['name' => 'Lille', 'lat' => 50.6278, 'lng' => 3.0583],
                ['name' => 'Reims', 'lat' => 49.2628, 'lng' => 4.0347],
                ['name' => 'Dijon', 'lat' => 47.3167, 'lng' => 5.0167],
                ['name' => 'Brest', 'lat' => 48.39, 'lng' => -4.49],
                ['name' => 'Tours', 'lat' => 47.3936, 'lng' => 0.6892],
                ['name' => 'Ajaccio', 'lat' => 41.9267, 'lng' => 8.7369],
            ],
            'ZA' => [
                ['name' => 'Johannesburg', 'lat' => -26.2044, 'lng' => 28.0456],
                ['name' => 'Cape Town', 'lat' => -33.9253, 'lng' => 18.4239],
                ['name' => 'Pretoria', 'lat' => -25.7461, 'lng' => 28.1881],
                ['name' => 'Soweto', 'lat' => -26.2678, 'lng' => 27.8585],
                ['name' => 'Pietermaritzburg', 'lat' => -29.6167, 'lng' => 30.3833],
                ['name' => 'Durban', 'lat' => -29.8833, 'lng' => 31.05],
                ['name' => 'Alexandria', 'lat' => -33.6533, 'lng' => 26.4083],
            ],
            'ES' => [
                ['name' => 'Madrid', 'lat' => 40.4169, 'lng' => -3.7033],
                ['name' => 'Barcelona', 'lat' => 41.3833, 'lng' => 2.1833],
                ['name' => 'Valencia', 'lat' => 39.47, 'lng' => -0.3764],
                ['name' => 'Bilbao', 'lat' => 43.2569, 'lng' => -2.9236],
                ['name' => 'Cordoba', 'lat' => 37.89, 'lng' => -4.78],
            ],
            'DE' => [
                ['name' => 'Berlin', 'lat' => 52.52, 'lng' => 13.405],
                ['name' => 'Munich', 'lat' => 48.1375, 'lng' => 11.575],
                ['name' => 'Hamburg', 'lat' => 53.55, 'lng' => 10.0],
                ['name' => 'Cologne', 'lat' => 50.9364, 'lng' => 6.9528],
                ['name' => 'Frankfurt', 'lat' => 50.1106, 'lng' => 8.6822],
                ['name' => 'Leipzig', 'lat' => 51.34, 'lng' => 12.375],
                ['name' => 'Bremen', 'lat' => 53.0758, 'lng' => 8.8072],
                ['name' => 'Dresden', 'lat' => 51.05, 'lng' => 13.74],
                ['name' => 'Hannover', 'lat' => 52.3667, 'lng' => 9.7167],
                ['name' => 'Nuremberg', 'lat' => 49.4539, 'lng' => 11.0775],
            ],
            'MA' => [
                ['name' => 'Casablanca', 'lat' => 33.5333, 'lng' => -7.5833],
                ['name' => 'Tangier', 'lat' => 35.7767, 'lng' => -5.8039],
                ['name' => 'Fes', 'lat' => 34.0433, 'lng' => -5.0033],
                ['name' => 'Marrakech', 'lat' => 31.63, 'lng' => -8.0089],
                ['name' => 'Sale', 'lat' => 34.0333, 'lng' => -6.8],
                ['name' => 'Rabat', 'lat' => 34.0209, 'lng' => -6.8416],
                ['name' => 'Agadir', 'lat' => 30.4214, 'lng' => -9.5831],
                ['name' => 'Meknes', 'lat' => 33.895, 'lng' => -5.5547],
                ['name' => 'Kenitra', 'lat' => 34.25, 'lng' => -6.5833],
                ['name' => 'El Kelaa des Srarhna', 'lat' => 32.0481, 'lng' => -7.4083],
                ['name' => 'Essaouira', 'lat' => 31.5131, 'lng' => -9.7697],
                ['name' => 'Demnat', 'lat' => 31.7311, 'lng' => -7.0361],
            ],
            'GR' => [
                ['name' => 'Athens', 'lat' => 37.9842, 'lng' => 23.7281],
            ],
            'IT' => [
                ['name' => 'Rome', 'lat' => 41.8931, 'lng' => 12.4828],
                ['name' => 'Milan', 'lat' => 45.4669, 'lng' => 9.19],
                ['name' => 'Naples', 'lat' => 40.8358, 'lng' => 14.2486],
                ['name' => 'Turin', 'lat' => 45.0792, 'lng' => 7.6761],
                ['name' => 'Palermo', 'lat' => 38.1157, 'lng' => 13.3613],
                ['name' => 'Genoa', 'lat' => 44.4072, 'lng' => 8.934],
                ['name' => 'Bologna', 'lat' => 44.4939, 'lng' => 11.3428],
                ['name' => 'Venice', 'lat' => 45.4397, 'lng' => 12.3319],
            ],
        
            'NL' => [
                ['name' => 'Amsterdam', 'lat' => 52.3728, 'lng' => 4.8936],
                ['name' => 'Rotterdam', 'lat' => 51.92, 'lng' => 4.48],
            ],
            'BE' => [
                ['name' => 'Brussels', 'lat' => 50.8467, 'lng' => 4.3525],
                ['name' => 'Antwerp', 'lat' => 51.2178, 'lng' => 4.4003],
                ['name' => 'Lille', 'lat' => 51.2383, 'lng' => 4.8242],
            ],
            'PT' => [
                ['name' => 'Lisbon', 'lat' => 38.7122, 'lng' => -9.134],
                ['name' => 'Porto', 'lat' => 41.1495, 'lng' => -8.6108],
            ],
            'BG' => [
                ['name' => 'Sofia', 'lat' => 42.6979, 'lng' => 23.3217],
                ['name' => 'Plovdiv', 'lat' => 42.1434, 'lng' => 24.751],
                ['name' => 'Varna', 'lat' => 43.2114, 'lng' => 27.9111],
            ],
            'AT' => [
                ['name' => 'Vienna', 'lat' => 48.2083, 'lng' => 16.3725],
                ['name' => 'Salzburg', 'lat' => 47.8, 'lng' => 13.045],
            ],
            'DZ' => [
                ['name' => 'Algiers', 'lat' => 36.7325, 'lng' => 3.0872],
                ['name' => 'Oran', 'lat' => 35.6969, 'lng' => -0.6331],
                ['name' => 'Constantine', 'lat' => 36.35, 'lng' => 6.6],
                ['name' => 'Bejaia', 'lat' => 36.7511, 'lng' => 5.0642],
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