<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Services\RestCountriesService;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    // ── Configuration ─────────────────────────────────────────────────────────

    /**
     * Population-based base quota tiers.
     * The first threshold the country's national population falls below
     * determines the base quota. PHP_INT_MAX is the catch-all.
     *
     *   <  1 000 000  → 1
     *   <  5 000 000  → 2
     *   < 10 000 000  → 3
     *   ≥ 10 000 000  → 5
     */
    private const BASE_QUOTA_TIERS = [
        1_000_000 => 2,
        5_000_000 => 4,
        10_000_000 => 6,
        20_000_000 => 10,
        50_000_000 => 15,
        PHP_INT_MAX => 20,
    ];

    /** Rank of the city whose population is used for the city-level pop bonus. */
    private const CITY_POP_RANK = 10;

    /** Bonus based on the size of the city at rank CITY_POP_RANK
     *  For instance if the 10th largest city of some country has more than 5 000 000 people
     *  we add an extra 20 cities to the country
     */
    private const POP_HIGH_THRESHOLD = 1_000_000;

    private const POP_HIGH_BONUS = 20;

    private const POP_MID_THRESHOLD = 500_000;

    private const POP_MID_BONUS = 10;

    private const IMPORTANT_COUNTRY_BONUS = 50;

    private const IMPORTANT_CURRENCY_BONUS = 15;

    private const IMPORTANT_LANGUAGE_BONUS = 15;

    private const IMPORTANT_COUNTRIES = [
        'US', 'CA', 'MX', 'BR', 'AR',
        'GB', 'FR', 'DE', 'ES', 'IT', 'PL', 'NL', 'BE', 'RU',
        'CN', 'IN', 'ID', 'TH', 'JP', 'KR', 'TR', 'SA', 'AE', 'PH',
        'MA', 'DZ', 'EG', 'ZA', 'KE', 'TA',
        'AU', 'NZ',
    ];

    private const IMPORTANT_CURRENCIES = ['USD', 'EUR', 'GBP'];

    private const IMPORTANT_LANGUAGES = ['en', 'fr', 'es', 'ar', 'zh', 'ru', 'de'];

    // ── Constructor ───────────────────────────────────────────────────────────

    public function __construct(private RestCountriesService $api) {}

    // ── Entry point ───────────────────────────────────────────────────────────

    public function run(): void
    {
        $csvPath = $this->resolveCsvPath();

        if (! file_exists($csvPath)) {
            $this->command?->error(
                "CSV file not found at:\n  {$csvPath}\n".
                'Drop the file there and re-run the seeder.'
            );

            return;
        }

        $countries = Country::with('currency')->get()->keyBy('iso_code');

        if ($countries->isEmpty()) {
            $this->command?->warn('No countries found — run CountrySeeder first.');

            return;
        }

        $this->command?->info('Fetching country population and language data …');
        $apiData = $this->api->getCountryMetadata($countries->keys()->all());

        $this->command?->info("Parsing {$csvPath} …");
        $rowsByIso2 = $this->parseCsv($csvPath, $countries->keys()->all());
        $this->command?->info('CSV parsed. Selecting cities …');

        // ── Log file setup ────────────────────────────────────────────────────
        $logDir = base_path('tmp');
        $logPath = $logDir.'/city_seeder_'.now()->format('Y-m-d_His').'.txt';

        if (! is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logHandle = fopen($logPath, 'w');
        $log = function (string $line) use ($logHandle): void {
            fwrite($logHandle, $line.PHP_EOL);
        };

        $log('City Seeder — '.now()->toDateTimeString());
        $log(str_repeat('-', 60));
        // ─────────────────────────────────────────────────────────────────────

        $totalInserted = 0;

        foreach ($countries as $iso2 => $country) {
            $rows = $rowsByIso2[$iso2] ?? [];

            if (empty($rows)) {
                $this->command?->warn("  [{$iso2}] {$country->name}: no rows in CSV — skipped.");
                $log(sprintf('  [%s] %s: no rows in CSV — skipped.', $iso2, $country->name));

                continue;
            }

            $countryPopulation = $apiData[$iso2]['population'] ?? 0;
            $countryLanguages = $apiData[$iso2]['languages'] ?? [];

            $selected = $this->selectCities($rows, $iso2, $country->currency?->code, $countryPopulation, $countryLanguages);
            $count = count($selected);

            foreach ($selected as $row) {
                City::create([
                    'name' => $row['city'] !== '' ? $row['city'] : $row['city_ascii'],
                    'country_id' => $country->id,
                    'lat' => (float) $row['lat'],
                    'lng' => (float) $row['lng'],
                ]);
            }

            $totalInserted += $count;

            $line = sprintf(
                '  [%s] %-20s %2d cities (quota %d, available %d)',
                $iso2,
                $country->name,
                $count,
                $this->computeQuota($rows, $iso2, $country->currency?->code, $countryPopulation, $countryLanguages),
                count($rows),
            );

            $this->command?->line($line);
            $log($line);
        }

        $summary = "Done. Inserted {$totalInserted} cities across {$countries->count()} countries.";
        $this->command?->info($summary);

        $log(str_repeat('-', 60));
        $log($summary);

        fclose($logHandle);

        $this->command?->info("Log written to: {$logPath}");
    }

    // ── File resolution ───────────────────────────────────────────────────────

    private function resolveCsvPath(): string
    {
        $filename = env('CITIES_CSV_FILE', 'worldcities.csv');

        return str_starts_with($filename, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:/', $filename)
            ? $filename
            : database_path("seeders/data/{$filename}");
    }

    // ── CSV parsing ───────────────────────────────────────────────────────────

    private function parseCsv(string $path, array $validIso2): array
    {
        $validSet = array_flip($validIso2);
        $grouped = [];

        $handle = fopen($path, 'r');

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);

            return [];
        }

        $headers = array_map('trim', $headers);
        $colCount = count($headers);

        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) !== $colCount) {
                continue;
            }

            $row = array_combine($headers, $line);
            $iso2 = trim($row['iso2'] ?? '');

            if (! isset($validSet[$iso2])) {
                continue;
            }

            if (trim($row['lat'] ?? '') === '' || trim($row['lng'] ?? '') === '') {
                continue;
            }

            $row['population'] = $this->normalisePopulation($row['population'] ?? '');
            $grouped[$iso2][] = $row;
        }

        fclose($handle);

        return $grouped;
    }

    private function normalisePopulation(string $raw): int
    {
        $clean = str_replace([',', ' ', "\u{00A0}"], '', trim($raw));

        return is_numeric($clean) ? (int) $clean : 0;
    }

    // ── City selection ────────────────────────────────────────────────────────

    private function selectCities(
        array $cities,
        string $iso2,
        ?string $currencyCode,
        int $countryPopulation,
        array $countryLanguages,
    ): array {
        usort($cities, fn ($a, $b) => $b['population'] <=> $a['population']);

        $quota = $this->computeQuota($cities, $iso2, $currencyCode, $countryPopulation, $countryLanguages);

        return array_slice($cities, 0, $quota);
    }

    private function computeQuota(
        array $sortedCities,
        string $iso2,
        ?string $currencyCode,
        int $countryPopulation,
        array $countryLanguages,
    ): int {
        $quota = 1;
        foreach (self::BASE_QUOTA_TIERS as $threshold => $tierQuota) {
            if ($countryPopulation < $threshold) {
                $quota = $tierQuota;
                break;
            }
        }

        $rankNPop = $sortedCities[self::CITY_POP_RANK - 1]['population'] ?? 0;

        if ($rankNPop > self::POP_HIGH_THRESHOLD) {
            $quota += self::POP_HIGH_BONUS;
        } elseif ($rankNPop > self::POP_MID_THRESHOLD) {
            $quota += self::POP_MID_BONUS;
        }

        if (in_array($iso2, self::IMPORTANT_COUNTRIES, true)) {
            $quota += self::IMPORTANT_COUNTRY_BONUS;
        }

        if ($currencyCode && in_array($currencyCode, self::IMPORTANT_CURRENCIES, true)) {
            $quota += self::IMPORTANT_CURRENCY_BONUS;
        }

        if (array_intersect($countryLanguages, self::IMPORTANT_LANGUAGES)) {
            $quota += self::IMPORTANT_LANGUAGE_BONUS;
        }

        return min($quota, count($sortedCities));
    }
}
