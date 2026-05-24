<div
    x-data="{
        open: false,
        search: '',
        activeIndex: -1,
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
            this.activeIndex      = -1;
            $dispatch('city-selection-changed', { cityId: city.id });
        },

        onCountryChange(countryId) {
            this.selectedCountryId = countryId ? parseInt(countryId) : null;
            this.selectedCityId    = null;
            this.selectedCityName  = '';
            this.search            = '';
            this.open              = false;
            this.activeIndex       = -1;
            $dispatch('country-changed',       { countryId: this.selectedCountryId });
            $dispatch('city-selection-changed', { cityId: null });
        },

        onFocus() {
            if (this.selectedCountryId) this.open = true;
        },

        handleEnter() {
            if (!this.open || this.filteredCities.length === 0) return;
            const idx = this.activeIndex >= 0 ? this.activeIndex : 0;
            this.selectCity(this.filteredCities[idx]);
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
            class="mt-1 block w-full border-neutral/50 rounded-md shadow-sm focus:ring focus:ring-primary/30
                   dark:bg-neutral-700 dark:border-neutral-600 dark:text-neutral-100"
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
            @input="search = $event.target.value; selectedCityName = ''; open = true; activeIndex = -1"
            @click.outside="open = false; activeIndex = -1; search = ''; $el.value = selectedCityName"
            :placeholder="selectedCountryId ? placeholderSearch : placeholderSelect"
            autocomplete="off"
            :disabled="!selectedCountryId"
            @keydown.down.prevent="activeIndex = Math.min(activeIndex + 1, filteredCities.length - 1); open = true"
            @keydown.up.prevent="activeIndex = Math.max(activeIndex - 1, 0)"
            @keydown.enter.prevent="handleEnter()"
            @keydown.escape.prevent="open = false; activeIndex = -1; search = ''; $el.value = selectedCityName"
            class="mt-1 block w-full border-neutral/50 rounded-md shadow-sm focus:ring focus:ring-primary/30
                   disabled:bg-neutral/10 disabled:cursor-not-allowed disabled:text-neutral
                   dark:bg-neutral-700 dark:border-neutral-600 dark:text-neutral-100
                   dark:placeholder-neutral-400 dark:disabled:bg-neutral-800 dark:disabled:text-neutral-500"
        />

        {{-- Hidden input carries city_id for form submission --}}
        <input type="hidden" name="city_id" :value="selectedCityId ?? ''">

        @if ($errors->has('city_id'))
            <p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $errors->first('city_id') }}</p>
        @endif

        <ul
            x-show="open && filteredCities.length > 0"
            x-ref="cityList"
            x-effect="
                if (activeIndex >= 0 && $refs.cityList) {
                    const items = $refs.cityList.querySelectorAll('li');
                    if (items[activeIndex]) items[activeIndex].scrollIntoView({ block: 'nearest' });
                }
            "
            style="display: none"
            class="absolute z-10 mt-1 w-full bg-white border border-neutral/20 rounded-md shadow-lg max-h-56 overflow-y-auto
                   dark:bg-neutral-700 dark:border-neutral-600"
        >
            <template x-for="(city, index) in filteredCities" :key="city.id">
                <li
                    @click="selectCity(city)"
                    :class="{
                        'bg-primary-100 dark:bg-primary-800/40 font-medium': selectedCityId === city.id,
                        'bg-neutral-100 dark:bg-neutral-600':                selectedCityId !== city.id && index === activeIndex,
                        'hover:bg-neutral-50 dark:hover:bg-neutral-600':     selectedCityId !== city.id && index !== activeIndex
                    }"
                    class="px-4 py-2 cursor-pointer text-sm text-neutral-900 dark:text-neutral-100"
                    x-text="city.name"
                ></li>
            </template>
        </ul>

        <p
            x-show="open && selectedCountryId && search && filteredCities.length === 0"
            class="mt-1 text-sm text-neutral-500 dark:text-neutral-400"
        >
            {{ __('No cities found.') }}
        </p>
    </div>

</div>
