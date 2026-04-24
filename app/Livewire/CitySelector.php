<?php

namespace App\Livewire;

use App\Models\City;
use App\Models\Country;
use Livewire\Component;
use Illuminate\View\View;

class CitySelector extends Component
{
    public int|null $selectedCountryId = null;
    public int|null $selectedCityId    = null;

    public function selectCity(int $cityId): void
    {
        $this->selectedCityId = $cityId;
    }

    public function render(): View
    {
        return view('livewire.city-selector', [
            'countries' => Country::orderBy('name')->get(),
            'cities'    => City::orderBy('name')->get(['id', 'name', 'country_id']),
        ]);
    }
}