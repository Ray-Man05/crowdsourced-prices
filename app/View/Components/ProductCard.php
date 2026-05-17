<?php

namespace App\View\Components;

use App\Models\City;
use App\Models\Currency;
use App\Models\Product;
use Illuminate\View\Component;

class ProductCard extends Component
{
    public ?string $formattedPrice;
    public ?string $formatted3xDaysPrice;

    public function __construct(
        public Product   $product,
        public City      $city,
        public Currency  $currency,
        public ?float    $averagePrice           = null,
        public ?float    $average3xDaysPrice     = null,
        public int       $days                   = 30,
        public ?string   $userStatus             = null,
        public ?string   $userEstimateFormatted  = null,
        public bool      $hasCityData            = false,
    ) {
        $this->formattedPrice = $this->averagePrice !== null
            ? $currency->format($this->averagePrice)
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
