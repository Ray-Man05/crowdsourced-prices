<?php

namespace App\View\Components;

use App\Models\City;
use App\Models\Currency;
use App\Models\Product;
use Illuminate\View\Component;

class ProductCard extends Component
{
    public ?float  $averagePrice;
    public ?float $globalPrice;
    public ?float $average3xDaysPrice;

    public ?string $formattedPrice;
    public ?string $formattedGlobalPrice;
    public ?string $formatted3xDaysPrice;
    

    public function __construct(
        public Product   $product,
        public City      $city,
        public Currency  $currency,
        public int       $days = 30,
    ) {
        // $this->averagePrice   = $product->averagePriceInCity($city, $currency, $days);
        $this->averagePrice   = $product->averagePriceInCity($city, $currency, $days);
        $this->average3xDaysPrice = $product->averagePriceInCity($city, $currency, $days * 3);
        $this->globalPrice = $product->averagePrice($currency, $days);

        $this->formattedPrice = $this->averagePrice !== null
            ? $currency->format($this->averagePrice)
            : null;
        $this->formattedGlobalPrice = $this->globalPrice !== null
            ? $currency->format($this->globalPrice)
            : null;
        $this->formatted3xDaysPrice = $this->average3xDaysPrice !== null
            ? $currency->format($this->average3xDaysPrice)
            : null;

    }

    public function render()
    {
        return view('components.product-card');
    }
}