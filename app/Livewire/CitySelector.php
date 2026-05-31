<?php

namespace App\Livewire;

use App\Models\City;
use App\Models\Country;
use Illuminate\View\View;
use Livewire\Component;

class CitySelector extends Component
{
    public ?int $selectedCountryId = null;

    public ?int $selectedCityId = null;

    public function mount(?int $initialCityId = null): void
    {
        if ($initialCityId) {
            $city = City::find($initialCityId);
            if ($city) {
                $this->selectedCityId = $city->id;
                $this->selectedCountryId = $city->country_id;
            }
        }
    }

    public function selectCity(int $cityId): void
    {
        $this->selectedCityId = $cityId;
    }

    public function render(): View
    {
        $initialCityName = $this->selectedCityId
            ? (City::find($this->selectedCityId)?->name ?? '')
            : '';

        return view('livewire.city-selector', [
            'countries' => Country::orderBy('name')->get(),
            'cities' => City::orderBy('name')->get(['id', 'name', 'country_id']),
            'initialCityName' => $initialCityName,
        ]);
    }
}
