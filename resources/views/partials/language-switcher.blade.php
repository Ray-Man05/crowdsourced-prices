<div class="flex gap-2 text-sm">
    <form method="POST" action="{{ route('locale.switch', 'en') }}">
        @csrf
        <button type="submit" class="{{ app()->getLocale() === 'en' ? 'font-bold underline' : 'text-gray-500' }}">
            EN
        </button>
    </form>
    <form method="POST" action="{{ route('locale.switch', 'fr') }}">
        @csrf
        <button type="submit" class="{{ app()->getLocale() === 'fr' ? 'font-bold underline' : 'text-gray-500' }}">
            FR
        </button>
    </form>
</div>