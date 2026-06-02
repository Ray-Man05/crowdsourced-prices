<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Currency;
use App\Services\RestCountriesService;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function __construct(private RestCountriesService $api) {}

    public function run(): void
    {
        $currencyIndex = Currency::all()->keyBy('code');

        $logDir = base_path('tmp/countries');
        $logPath = $logDir.'/country_seeder_'.now()->format('Y-m-d_His').'.txt';

        if (! is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logHandle = fopen($logPath, 'w');

        foreach ($this->api->getCountries() as $country) {
            $currency = $currencyIndex[$country['currency_code']] ?? null;

            if (! $currency) {
                continue;
            }

            Country::create([
                'name' => $country['name'],
                'iso_code' => $country['iso_code'],
                'currency_id' => $currency->id,
            ]);

            fwrite($logHandle, sprintf("[%s] %s\n", $country['iso_code'], $country['name']));
        }

        fclose($logHandle);

        $this->command?->info("Country log written to: {$logPath}");
    }
}
