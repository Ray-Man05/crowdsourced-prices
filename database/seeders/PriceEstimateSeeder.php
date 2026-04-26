<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\PriceEstimate;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PriceEstimateSeeder extends Seeder
{
    /**
     * Mean prices in USD per product name.
     * Adjust these to tune the seeded data.
     */
    private array $means = [
        // Fruits (per kg)
        'Banana'         => 1.20,
        'Apple'          => 2.50,
        'Orange'         => 1.80,
        'Strawberry'     => 4.00,
        'Mango'          => 3.50,
        // Vegetables (per kg)
        'Tomato'         => 2.00,
        'Potato'         => 1.00,
        'Onion'          => 0.90,
        'Carrot'         => 1.10,
        'Spinach'        => 2.80,
        // Dairy and Eggs
        'Whole Milk'     => 1.20,  // per liter
        'Butter'         => 8.00,  // per kg
        'Cheddar Cheese' => 10.00, // per kg
        'Yogurt'         => 3.50,  // per kg
        'Eggs'           => 0.30,  // per piece
        // Meat and Poultry (per kg)
        'Chicken Breast' => 7.00,
        'Ground Beef'    => 10.00,
        'Lamb Chops'     => 14.00,
        'Pork Ribs'      => 9.00,
        'Turkey'         => 6.00,
        // Herbs and Spices (per 100g)
        'Black Pepper'   => 1.50,
        'Cumin'          => 1.20,
        'Paprika'        => 1.00,
        'Cinnamon'       => 1.30,
        'Turmeric'       => 1.10,
        // Condiments
        'Olive Oil'      => 8.00,  // per liter
        'Ketchup'        => 3.00,  // per kg
        'Mustard'        => 3.50,  // per kg
        'Soy Sauce'      => 4.00,  // per liter
        'Honey'          => 9.00,  // per kg
    ];

    /**
     * Standard deviation as a fraction of the mean.
     * 0.10 = ±10% spread around the mean.
     */
    private float $spreadFraction = 0.10;

    public function run(): void
    {
        $products   = Product::all()->keyBy('name');
        $users      = User::all();
        $currencies = Currency::all()->keyBy('code');
        $usd        = $currencies['USD'];

        foreach ($products as $name => $product) {
            $mean            = $this->means[$name] ?? 1.00;
            $estimateCount   = random_int(40, 80);

            for ($i = 0; $i < $estimateCount; $i++) {
                $user     = $users->random();

                // $currency = $currencies->random();
                $currency = $user->city?->currency ?? $currencies['USD'];

                // Convert mean from USD to the estimate's currency
                $meanInCurrency = $usd->convert($mean, $currency) ?? $mean;

                // Gaussian-approximated random price around the mean
                $price = $this->gaussianRandom($meanInCurrency, $meanInCurrency * $this->spreadFraction);
                $price = max(0.01, round($price, 2));

                // Random timestamp spread over the past year
                $recordedAt = Carbon::now()->subDays(random_int(0, 365));

                PriceEstimate::create([
                    'price'       => $price,
                    'user_id'     => $user->id,
                    'product_id'  => $product->id,
                    'currency_id' => $currency->id,
                    'city_id'     => $user->city_id,
                    'recorded_at' => $recordedAt,
                ]);
            }
        }
    }

    /**
     * Box-Muller transform: produces a normally distributed random float.
     */
    private function gaussianRandom(float $mean, float $stdDev): float
    {
        $u1 = 1.0 - lcg_value();
        $u2 = 1.0 - lcg_value();
        $z  = sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);
        return $mean + $stdDev * $z;
    }
}