<div
    x-data="{
        open: false,
        search: '',
        selectedCityId: {{ $this->selectedCityId ?? 'null' }},
        selectedCityName: @js($initialCityName),
        selectedCountryId: {{ $this->selectedCountryId ?? 'null' }},
        placeholderSearch: @js(__('Type to search...')),
        placeholderSelect: @js(__('Select a country first')),
        cities: {{ Js::from($cities) }},

        get filteredCities() {
            return this.cities.filter(city => {
                const matchesCountry = !this.selectedCountryId
                    || city.country_id === this.selectedCountryId;
                const matchesSearch = !this.search
                    || city.name.toLowerCase().includes(this.search.toLowerCase());
                return matchesCountry && matchesSearch;
            });
        },

        selectCity(city) {
            this.selectedCityId   = city.id;
            this.selectedCityName = city.name;
            this.search           = '';
            this.open             = false;
        },

        onCountryChange(countryId) {
            this.selectedCountryId = countryId ? parseInt(countryId) : null;
            this.selectedCityId    = null;
            this.selectedCityName  = '';
            this.search            = '';
            this.open              = false;
            $dispatch('country-changed', { countryId: this.selectedCountryId });
        },

        onFocus() {
            if (this.selectedCountryId) {
                this.open = true;
            }
        }
    }"
    class="space-y-4 mt-4"
>
    {{-- Country dropdown --}}
    <div>
        <x-input-label for="country" :value="__('Country')" />
        <select
            id="country"
            x-init="$el.value = selectedCountryId || ''"
            @change="onCountryChange($event.target.value)"
            class="mt-1 block w-full border-neutral/50 rounded-md shadow-sm focus:ring focus:ring-primary/30"
        >
            <option value="">{{ __('Select a country') }}</option>
            @foreach ($countries as $country)
                <option value="{{ $country->id }}">{{ $country->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- City search + dropdown --}}
    <div class="relative">
        <x-input-label for="city_search" :value="__('City')" />

        <input
            id="city_search"
            type="text"
            :value="selectedCityName || search"
            @focus="onFocus"
            @input="search = $event.target.value; selectedCityName = ''; open = true"
            @click.outside="open = false; search = ''; $el.value = selectedCityName"
            :placeholder="selectedCountryId ? placeholderSearch : placeholderSelect"
            autocomplete="off"
            :disabled="!selectedCountryId"
            class="mt-1 block w-full border-neutral/50 rounded-md shadow-sm focus:ring focus:ring-primary/30
                   disabled:bg-neutral/10 disabled:cursor-not-allowed disabled:text-neutral"
        />

        {{-- Hidden input carries city_id for form submission --}}
        <input type="hidden" name="city_id" :value="selectedCityId">

        @if ($errors->has('city_id'))
            <p class="mt-1 text-sm text-error">{{ $errors->first('city_id') }}</p>
        @endif

        <ul
            x-show="open && filteredCities.length > 0"
            style="display: none"
            class="absolute z-10 mt-1 w-full bg-white border border-neutral/20 rounded-md shadow-lg max-h-56 overflow-y-auto"
        >
            <template x-for="city in filteredCities" :key="city.id">
                <li
                    @click="selectCity(city)"
                    :class="selectedCityId === city.id ? 'bg-highlight font-medium' : 'hover:bg-highlight'"
                    class="px-4 py-2 cursor-pointer text-sm"
                    x-text="city.name"
                ></li>
            </template>
        </ul>

        <p
            x-show="open && selectedCountryId && search && filteredCities.length === 0"
            class="mt-1 text-sm text-neutral"
        >
            {{ __('No cities found.') }}
        </p>
    </div>

</div>
