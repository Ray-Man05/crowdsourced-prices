<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateExchangeRates extends Command
{
    protected $signature = 'exchange-rates:update';

    protected $description = 'Fetch latest exchange rates from the API and upsert them.';

    public function handle(): int
    {
        $apiKey = env('EXCHANGERATE_API_KEY');

        if (! $apiKey) {
            $this->warn('EXCHANGERATE_API_KEY is not set — skipping.');

            return self::SUCCESS;
        }

        $currencies = Currency::all()->keyBy('code');

        if ($currencies->isEmpty()) {
            $this->warn('No currencies found — run CurrencySeeder first.');

            return self::FAILURE;
        }

        if (! $currencies->has('USD')) {
            $this->error('USD not found in currencies table.');

            return self::FAILURE;
        }

        $usdRates = $this->fetchRates('USD', $apiKey);

        if (empty($usdRates)) {
            $this->error('Could not fetch exchange rates — aborting.');

            return self::FAILURE;
        }

        $nonUsd = $currencies
            ->filter(fn ($currency, $code) => $code !== 'USD' && isset($usdRates[$code]) && $usdRates[$code] > 0);

        $skipped = $currencies->count() - 1 - $nonUsd->count();
        if ($skipped > 0) {
            $this->warn("  {$skipped} currencies had no API rate and were skipped.");
        }

        $records = [];
        $codes = $nonUsd->keys()->all();
        $n = count($codes);

        foreach ($codes as $code) {
            array_push($records, ...ExchangeRate::buildRecordPair($currencies['USD'], $nonUsd[$code], $usdRates[$code]));
        }

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $crossRate = $usdRates[$codes[$j]] / $usdRates[$codes[$i]];
                array_push($records, ...ExchangeRate::buildRecordPair($nonUsd[$codes[$i]], $nonUsd[$codes[$j]], $crossRate));
            }
        }

        foreach (array_chunk($records, 1000) as $chunk) {
            ExchangeRate::upsert($chunk, uniqueBy: ['from_currency_id', 'to_currency_id']);
        }

        $totalPairs = $n + ($n * ($n - 1) / 2);
        $this->info("Done. {$totalPairs} pairs upserted across {$currencies->count()} currencies.");

        return self::SUCCESS;
    }

    private function fetchRates(string $baseCode, string $apiKey): array
    {
        $url = "https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$baseCode}";

        try {
            $response = Http::timeout(10)->get($url);
        } catch (\Throwable $e) {
            $this->error("HTTP request failed: {$e->getMessage()}");

            return [];
        }

        if (! $response->successful()) {
            $this->error("API returned HTTP {$response->status()}.");

            return [];
        }

        $data = $response->json();

        if (($data['result'] ?? '') !== 'success') {
            $this->error('API error: '.($data['error-type'] ?? 'unknown'));

            return [];
        }

        return $data['conversion_rates'] ?? [];
    }
}
