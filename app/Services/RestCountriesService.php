<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RestCountriesService
{
    private const BASE_URL = 'https://restcountries.com/v3.1';

    /**
     * Fetch currency data for all UN member states.
     * Returns an array of ['name', 'code', 'symbol'] for every unique currency
     * found among countries with exactly one currency.
     *
     * @return list<array{name: string, code: string, symbol: string|null}>
     */
    public function getUniqueCurrencies(): array
    {
        $data = $this->fetchAll('currencies,unMember');

        $seen = [];
        $currencies = [];

        foreach ($data as $country) {
            if (! $country['unMember']) {
                continue;
            }

            $countryCurrencies = $country['currencies'] ?? [];

            if (count($countryCurrencies) !== 1) {
                continue;
            }

            foreach ($countryCurrencies as $code => $meta) {
                if (isset($seen[$code])) {
                    continue;
                }

                $seen[$code] = true;
                $currencies[] = [
                    'name' => $meta['name'],
                    'code' => $code,
                    'symbol' => $meta['symbol'] ?? null,
                ];
            }
        }

        usort($currencies, fn ($a, $b) => strcmp($a['code'], $b['code']));

        return $currencies;
    }

    /**
     * Fetch name, ISO-2 code and currency code for all UN member states
     * that have exactly one currency.
     *
     * @return list<array{name: string, iso_code: string, currency_code: string}>
     */
    public function getCountries(): array
    {
        $data = $this->fetchAll('name,cca2,currencies,unMember');

        $countries = [];

        foreach ($data as $country) {
            if (! $country['unMember']) {
                continue;
            }

            $countryCurrencies = $country['currencies'] ?? [];

            if (count($countryCurrencies) !== 1) {
                continue;
            }

            $countries[] = [
                'name' => $country['name']['common'],
                'iso_code' => $country['cca2'],
                'currency_code' => array_key_first($countryCurrencies),
            ];
        }

        usort($countries, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $countries;
    }

    /**
     * Fetch population and spoken languages for a given set of ISO-2 codes.
     *
     * Returns an array keyed by ISO-2 code:
     *   [ 'FR' => ['population' => 67750000, 'languages' => ['fr']], ... ]
     *
     * Languages are ISO 639-1 codes (the keys of the `languages` map in the
     * REST Countries response, e.g. ['en', 'fr']).
     *
     * @param  string[]  $iso2Codes
     * @return array<string, array{population: int, languages: string[]}>
     */
    public function getCountryMetadata(array $iso2Codes): array
    {
        $data = $this->fetchAll('cca2,population,languages');
        $result = [];

        foreach ($data as $entry) {
            $iso2 = $entry['cca2'] ?? '';

            if (! in_array($iso2, $iso2Codes, true)) {
                continue;
            }

            $result[$iso2] = [
                'population' => (int) ($entry['population'] ?? 0),
                'languages' => array_keys($entry['languages'] ?? []),
            ];
        }

        return $result;
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private function fetchAll(string $fields): array
    {
        return Http::get(self::BASE_URL.'/all', ['fields' => $fields])
            ->throw()
            ->json();
    }
}
