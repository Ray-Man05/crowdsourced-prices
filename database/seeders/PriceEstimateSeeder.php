<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PriceEstimateSeeder extends Seeder
{
    private const CHUNK_SIZE = 1000;

    private array $means = [
        // Fruits (per kg)
        'Banana' => 1.20,
        'Apple' => 1.50,
        'Orange' => 1.80,
        'Strawberry' => 7.00,
        'Mango' => 4.50,
        // Vegetables (per kg)
        'Tomato' => 2.00,
        'Potato' => 1.00,
        'Onion' => 0.90,
        'Carrot' => 1.10,
        'Spinach' => 2.80,
        // Dairy and Eggs
        'Whole Milk' => 1.20,
        'Butter' => 8.00,
        'Cheddar Cheese' => 10.00,
        'Yogurt' => 3.50,
        'Eggs' => 0.30,
        // Meat and Poultry (per kg)
        'Chicken Breast' => 7.00,
        'Chicken Thighs' => 6.00,
        'Ground Beef' => 10.00,
        'Lamb Chops' => 14.00,
        'Pork Ribs' => 9.00,
        'Turkey' => 6.00,
        // Herbs and Spices (per kg)
        'Black Pepper' => 12.50,
        'Cumin' => 10.20,
        'Paprika' => 10.00,
        'Cinnamon' => 11.30,
        'Turmeric' => 9.10,
        // Condiments
        'Olive Oil' => 8.00,
        'Ketchup' => 3.00,
        'Mustard' => 3.50,
        'Soy Sauce' => 4.00,
        'Honey' => 9.00,
    ];

    private float $spreadFraction = 0.10;

    public function run(): void
    {
        $products = Product::all()->keyBy('name');
        $currencies = Currency::all()->keyBy('code');
        $usd = $currencies['USD'];

        // Eager-load the full chain: city → country → currency
        $users = User::with('city.country.currency')->get();

        // Pre-build rate lookup: [fromCurrencyId][toCurrencyId] => rate
        $rates = [];
        ExchangeRate::all(['from_currency_id', 'to_currency_id', 'rate'])
            ->each(function ($r) use (&$rates) {
                $rates[$r->from_currency_id][$r->to_currency_id] = $r->rate;
            });

        $convertFromUsd = function (float $amount, Currency $target) use ($usd, $rates): float {
            if ($usd->id === $target->id) {
                return $amount;
            }
            $rate = $rates[$usd->id][$target->id] ?? null;

            return $rate !== null ? $amount * $rate : $amount;
        };

        // Build user → currency map (user_id => Currency)
        // Fallback to USD
        $userCurrencyMap = $users->mapWithKeys(function ($user) use ($usd) {
            $currency = $user->city?->country?->currency ?? $usd;

            return [$user->id => $currency];
        });

        // Pre-compute means in every currency actually used by users
        $usedCurrencies = $userCurrencyMap->unique('id');
        $meansInCurrency = [];
        foreach ($this->means as $productName => $usdMean) {
            foreach ($usedCurrencies as $currency) {
                $meansInCurrency[$productName][$currency->id] = $convertFromUsd($usdMean, $currency);
            }
        }

        $usersArray = $users->values()->all();
        $userCount = count($usersArray);
        $now = now()->toDateTimeString();
        $rows = [];

        foreach ($products as $name => $product) {
            $estimateCount = random_int(5000, 10000);

            for ($i = 0; $i < $estimateCount; $i++) {
                $user = $usersArray[random_int(0, $userCount - 1)];
                $currency = $userCurrencyMap[$user->id];

                $mean = $meansInCurrency[$name][$currency->id]
                      ?? $convertFromUsd($this->means[$name] ?? 1.00, $currency);
                $price = max(0.01, round($this->gaussianRandom($mean, $mean * $this->spreadFraction), 2));

                $rows[] = [
                    'price' => $price,
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'currency_id' => $currency->id,
                    'city_id' => $user->city_id,
                    'recorded_at' => Carbon::now()->subDays(random_int(0, 365))->toDateTimeString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($rows) >= self::CHUNK_SIZE) {
                    DB::table('price_estimates')->insert($rows);
                    $rows = [];
                }
            }
        }

        if (! empty($rows)) {
            DB::table('price_estimates')->insert($rows);
        }
    }

    private function gaussianRandom(float $mean, float $stdDev): float
    {
        $u1 = 1.0 - lcg_value();
        $u2 = 1.0 - lcg_value();
        $z = sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);

        return $mean + $stdDev * $z;
    }
}
