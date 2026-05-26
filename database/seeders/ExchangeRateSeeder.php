<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        $apiKey = env('EXCHANGERATE_API_KEY');

        if (! $apiKey) {
            $this->command?->warn('EXCHANGERATE_API_KEY is not set — skipping.');
            return;
        }

        $currencies = Currency::all()->keyBy('code');

        if ($currencies->isEmpty()) {
            $this->command?->warn('No currencies found — run CurrencySeeder first.');
            return;
        }

        if (! $currencies->has('USD')) {
            $this->command?->error('USD not found in currencies table.');
            return;
        }

        $usdRates = $this->fetchRates('USD', $apiKey);

        if (empty($usdRates)) {
            $this->command?->error('Could not fetch exchange rates — aborting.');
            return;
        }

        // Keep only currencies present in both the DB and the API response
        $nonUsd = $currencies
            ->filter(fn($currency, $code) => $code !== 'USD' && isset($usdRates[$code]) && $usdRates[$code] > 0);

        $skipped = $currencies->count() - 1 - $nonUsd->count();
        if ($skipped > 0) {
            $this->command?->warn("  {$skipped} currencies had no API rate and were skipped.");
        }

        // Build every record pair (forward + inverse) into a flat array.
        // USD <-> X comes directly from the API.
        // A <-> B is derived as: rate(A→B) = usdToB / usdToA
        $records = [];
        $codes   = $nonUsd->keys()->all();
        $n       = count($codes);

        foreach ($codes as $code) {
            array_push($records, ...ExchangeRate::buildRecordPair($currencies['USD'], $nonUsd[$code], $usdRates[$code]));
        }

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $crossRate = $usdRates[$codes[$j]] / $usdRates[$codes[$i]];
                array_push($records, ...ExchangeRate::buildRecordPair($nonUsd[$codes[$i]], $nonUsd[$codes[$j]], $crossRate));
            }
        }

        // Write everything in one go, chunked to stay within DB parameter limits
        foreach (array_chunk($records, 1000) as $chunk) {
            ExchangeRate::upsert($chunk, uniqueBy: ['from_currency_id', 'to_currency_id']);
        }

        $totalPairs = $n + ($n * ($n - 1) / 2);
        $this->command?->info("Done. {$totalPairs} pairs upserted across {$currencies->count()} currencies.");
    }

    private function fetchRates(string $baseCode, string $apiKey): array
    {
        $url = "https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$baseCode}";

        try {
            $response = Http::timeout(10)->get($url);
        } catch (\Throwable $e) {
            $this->command?->error("HTTP request failed: {$e->getMessage()}");
            return [];
        }

        if (! $response->successful()) {
            $this->command?->error("API returned HTTP {$response->status()}.");
            return [];
        }

        $data = $response->json();

        if (($data['result'] ?? '') !== 'success') {
            $this->command?->error('API error: ' . ($data['error-type'] ?? 'unknown'));
            return [];
        }

        return $data['conversion_rates'] ?? [];
    }
}