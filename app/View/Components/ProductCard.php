<?php

namespace App\View\Components;

use App\Models\City;
use App\Models\Currency;
use App\Models\Product;
use Illuminate\View\Component;

class ProductCard extends Component
{
    public ?float  $averagePrice;
    public ?string $formattedPrice;

    public function __construct(
        public Product   $product,
        public City      $city,
        public Currency  $currency,
        public int       $days = 30,
    ) {
        // $this->averagePrice   = $product->averagePriceInCity($city, $currency, $days);
        $this->averagePrice   = $product->averagePriceInCity($city, $currency, $days);
        $this->formattedPrice = $this->averagePrice !== null
            ? $currency->format($this->averagePrice)
            : null;
    }

    public function render()
    {
        return view('components.product-card');
    }
}