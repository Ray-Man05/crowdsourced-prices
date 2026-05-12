<section>
    <header>
        <h2 class="text-lg font-medium text-neutral-900 dark:text-neutral-100">
            {{ __('Location & Currency') }}
        </h2>
        <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
            {{ __('Change where you\'re located and your preferred currency.') }}
        </p>
    </header>

    {{-- Weekly-change notice (always visible) --}}
    <div class="mt-4 flex items-start gap-3 rounded-lg border border-warning-300 bg-warning-50
                dark:border-warning-700 dark:bg-warning-900/20 px-4 py-3 text-sm
                text-warning-800 dark:text-warning-300">
        <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        {{ __('This can only be changed once per week.') }}
    </div>

    @php $cooldownEndsAt = $user->locationCooldownEndsAt(); @endphp

    {{-- Cooldown active --}}
    @if ($cooldownEndsAt)
        <div class="mt-3 flex items-start gap-3 rounded-lg border border-error-300 bg-error-50
                    dark:border-error-700 dark:bg-error-900/20 px-4 py-3 text-sm
                    text-error-800 dark:text-error-300">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 15v2m0-10v5m-9 4a9 9 0 1118 0 9 9 0 01-18 0z"/>
            </svg>
            {{ __('Locked until :date.', ['date' => $cooldownEndsAt->isoFormat('LL')]) }}
        </div>
    @endif

    {{-- Success flash --}}
    @if (session('status') === 'location-updated')
        <p x-data="{ show: true }" x-show="show" x-transition
           x-init="setTimeout(() => show = false, 3000)"
           class="mt-3 text-sm font-medium text-success-600 dark:text-success-400">
            {{ __('Saved.') }}
        </p>
    @endif

    <form
        method="post"
        action="{{ route('profile.location') }}"
        class="mt-6 space-y-6 {{ $cooldownEndsAt ? 'opacity-60 pointer-events-none' : '' }}"
        x-data="{
            countryCurrencies: {{ Js::from($countryCurrencies) }}
        }"
        x-on:country-changed.window="
            let currId = countryCurrencies[$event.detail.countryId];
            if (currId) $refs.currencySelect.value = currId;
        "
    >
        @csrf
        @method('patch')

        {{-- City selector (reused component) --}}
        <livewire:city-selector :initial-city-id="$user->city_id" />

        {{-- Currency --}}
        <div>
            <x-input-label for="currency_id" :value="__('Currency')" />
            <select
                id="currency_id"
                name="currency_id"
                x-ref="currencySelect"
                class="mt-1 block w-full border-neutral/50 rounded-md shadow-sm
                       focus:ring focus:ring-primary/30 dark:bg-neutral-700
                       dark:border-neutral-600 dark:text-neutral-100"
            >
                <option value="">{{ __('Select a currency') }}</option>
                @foreach ($currencies as $currency)
                    <option
                        value="{{ $currency->id }}"
                        {{ $currency->id == ($user->currency_id ?? $user->effectiveCurrency()?->id) ? 'selected' : '' }}
                    >
                        {{ $currency->name }} ({{ $currency->symbol }})
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('currency_id')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button :disabled="$cooldownEndsAt !== null">
                {{ __('Save') }}
            </x-primary-button>
        </div>
    </form>
</section>
