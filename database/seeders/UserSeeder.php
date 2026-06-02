<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    private const BULK_PER_CITY = 30;

    // private const MAX_CITIES    = 500;
    private const CHUNK_SIZE = 100;

    public function run(): void
    {
        $maxCities = random_int(100, 200);

        $locales = ['en', 'fr'];
        $themes = ['light', 'dark'];
        $now = now()->toDateTimeString();
        $password = Hash::make('password'); // hash once, reuse everywhere

        // ── Pre-load data ──────────────────────────────────────────────────────
        $allCities = City::all();
        $citiesToSeed = $allCities->shuffle()->take($maxCities);
        $countryMap = Country::all()->keyBy('name');
        $citiesByCountry = $citiesToSeed->groupBy('country_id');

        // ── Admin account ──────────────────────────────────────────────────────
        $rows[] = [
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => $password,
            'role' => 'admin',
            'locale' => 'en',
            'theme' => 'light',
            'city_id' => $citiesToSeed->first()?->id,
            'currency_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // ── Named users ────────────────────────────────────────────────────────
        $namedUsers = [
            // United States
            ['name' => 'Ulysses S. Grant',           'country' => 'United States'],
            ['name' => 'Stonewall Jackson',          'country' => 'United States'],
            ['name' => 'Frederick Douglass',         'country' => 'United States'],
            ['name' => 'Abraham Lincoln',            'country' => 'United States'],
            ['name' => 'Franklin Delano Roosevelt',  'country' => 'United States'],
            ['name' => 'W.E.B. Du Bois',             'country' => 'United States'],
            ['name' => 'Booker T. Washington',       'country' => 'United States'],
            ['name' => 'Mark Twain',                 'country' => 'United States'],
            ['name' => 'Martin Luther King Jr',      'country' => 'United States'],
            ['name' => 'Malcolm X',                  'country' => 'United States'],
            ['name' => 'George Washington',          'country' => 'United States'],

            // United Kingdom
            ['name' => 'William Shakespeare',        'country' => 'United Kingdom'],
            ['name' => 'Isaac Newton',               'country' => 'United Kingdom'],
            ['name' => 'Charles Darwin',             'country' => 'United Kingdom'],
            ['name' => 'Winston Churchill',          'country' => 'United Kingdom'],
            ['name' => 'Alan Turing',                'country' => 'United Kingdom'],
            ['name' => 'Queen Victoria',             'country' => 'United Kingdom'],

            // France
            ['name' => 'Joan of Arc',                'country' => 'France'],
            ['name' => 'Napoleon Bonaparte',         'country' => 'France'],
            ['name' => 'Voltaire',                   'country' => 'France'],
            ['name' => 'René Descartes',             'country' => 'France'],
            ['name' => 'Albert Camus',               'country' => 'France'],
            ['name' => 'Louis XIV',                  'country' => 'France'],
            ['name' => 'Maximilien Robespierre',     'country' => 'France'],

            // Germany
            ['name' => 'Immanuel Kant',              'country' => 'Germany'],
            ['name' => 'Otto von Bismarck',          'country' => 'Germany'],
            ['name' => 'Johannes Gutenberg',         'country' => 'Germany'],
            ['name' => 'Martin Luther',              'country' => 'Germany'],

            // Austria
            ['name' => 'Wolfgang Amadeus Mozart',    'country' => 'Austria'],

            // Spain
            ['name' => 'Miguel de Cervantes',        'country' => 'Spain'],
            ['name' => 'Isabella I of Castile',      'country' => 'Spain'],
            ['name' => 'Ferdinand II of Aragon',     'country' => 'Spain'],
            ['name' => 'Francisco Goya',             'country' => 'Spain'],
            ['name' => 'Hernán Cortés',              'country' => 'Spain'],

            // Portugal
            ['name' => 'Vasco da Gama',              'country' => 'Portugal'],
            ['name' => 'Prince Henry the Navigator', 'country' => 'Portugal'],

            // Italy
            ['name' => 'Dante Alighieri',            'country' => 'Italy'],
            ['name' => 'Leonardo da Vinci',          'country' => 'Italy'],
            ['name' => 'Niccolò Machiavelli',        'country' => 'Italy'],
            ['name' => 'Marco Polo',                 'country' => 'Italy'],
            ['name' => 'Amerigo Vespucci',           'country' => 'Italy'],
            ['name' => 'Christopher Columbus',       'country' => 'Italy'],
            ['name' => 'Julius Caesar',              'country' => 'Italy'],
            ['name' => 'Augustus',                   'country' => 'Italy'],
            ['name' => 'Marcus Aurelius',            'country' => 'Italy'],
            ['name' => 'Gaius Marius',               'country' => 'Italy'],
            ['name' => 'Marcus Tullius Cicero',      'country' => 'Italy'],

            // Greece
            ['name' => 'Alexander the Great',        'country' => 'Greece'],
            ['name' => 'Achilles',                   'country' => 'Greece'],
            ['name' => 'Odysseus',                   'country' => 'Greece'],
            ['name' => 'Heracles',                   'country' => 'Greece'],
            ['name' => 'Zeus',                       'country' => 'Greece'],
            ['name' => 'Poseidon',                   'country' => 'Greece'],
            ['name' => 'Athena',                     'country' => 'Greece'],
            ['name' => 'Ares',                       'country' => 'Greece'],
            ['name' => 'Hera',                       'country' => 'Greece'],
            ['name' => 'Demeter',                    'country' => 'Greece'],
            ['name' => 'Aphrodite',                  'country' => 'Greece'],
            ['name' => 'Hephaestus',                 'country' => 'Greece'],
            ['name' => 'Hermes',                     'country' => 'Greece'],
            ['name' => 'Hestia',                     'country' => 'Greece'],
            ['name' => 'Dionysus',                   'country' => 'Greece'],
            ['name' => 'Apollo',                     'country' => 'Greece'],
            ['name' => 'Artemis',                    'country' => 'Greece'],
            ['name' => 'Hades',                      'country' => 'Greece'],
            ['name' => 'Persephone',                 'country' => 'Greece'],

            // Netherlands
            ['name' => 'Erasmus of Rotterdam',       'country' => 'Netherlands'],
            ['name' => 'Rembrandt van Rijn',         'country' => 'Netherlands'],
            ['name' => 'Baruch Spinoza',             'country' => 'Netherlands'],
            ['name' => 'William of Orange',          'country' => 'Netherlands'],

            // Belgium
            ['name' => 'Hergé',                      'country' => 'Belgium'],
            ['name' => 'Leopold II',                 'country' => 'Belgium'],

            // Russia
            ['name' => 'Peter the Great',            'country' => 'Russia'],
            ['name' => 'Catherine the Great',        'country' => 'Russia'],
            ['name' => 'Ivan the Terrible',          'country' => 'Russia'],
            ['name' => 'Leo Tolstoy',                'country' => 'Russia'],
            ['name' => 'Fyodor Dostoevsky',          'country' => 'Russia'],
            ['name' => 'Yuri Gagarin',               'country' => 'Russia'],
            ['name' => 'Mikhail Gorbachev',          'country' => 'Russia'],

            // Georgia
            ['name' => 'Tamar Mepe',                 'country' => 'Georgia'],

            // Morocco
            ['name' => 'Ibn Battuta',                'country' => 'Morocco'],
            ['name' => 'Ahmad al-Mansur',            'country' => 'Morocco'],
            ['name' => 'Yusuf ibn Tashfin',          'country' => 'Morocco'],
            ['name' => 'Fatima al-Fihri',            'country' => 'Morocco'],

            // Algeria
            ['name' => 'Augustine of Hippo',         'country' => 'Algeria'],

            // Egypt
            ['name' => 'Cleopatra VII',              'country' => 'Egypt'],
            ['name' => 'Ramses II',                  'country' => 'Egypt'],
            ['name' => 'Akhenaten',                  'country' => 'Egypt'],
            ['name' => 'Tutankhamun',                'country' => 'Egypt'],
            ['name' => 'Imhotep',                    'country' => 'Egypt'],
            ['name' => 'Hatshepsut',                 'country' => 'Egypt'],
            ['name' => 'Saladin',                    'country' => 'Egypt'],
            ['name' => 'Gamal Abdel Nasser',         'country' => 'Egypt'],

            // Ethiopia
            ['name' => 'Haile Selassie',             'country' => 'Ethiopia'],
            ['name' => 'Menelik II',                 'country' => 'Ethiopia'],

            // Nigeria
            ['name' => 'Chinua Achebe',              'country' => 'Nigeria'],

            // Ghana
            ['name' => 'Kofi Annan',                 'country' => 'Ghana'],

            // South Africa
            ['name' => 'Shaka kaSenzangakhona',      'country' => 'South Africa'],
            ['name' => 'Nelson Mandela',             'country' => 'South Africa'],
            ['name' => 'Desmond Tutu',               'country' => 'South Africa'],

            // China
            ['name' => 'Confucius',                  'country' => 'China'],
            ['name' => 'Laozi',                      'country' => 'China'],
            ['name' => 'Han Fei Tzu',                'country' => 'China'],
            ['name' => 'Sun Tzu',                    'country' => 'China'],
            ['name' => 'Qin Shi Huang',              'country' => 'China'],
            ['name' => 'Wu Zetian',                  'country' => 'China'],
            ['name' => 'Zheng He',                   'country' => 'China'],
            ['name' => 'Cao Cao',                    'country' => 'China'],
            ['name' => 'Lu Bu',                      'country' => 'China'],
            ['name' => 'Liu Bei',                    'country' => 'China'],
            ['name' => 'Zhang Fei',                  'country' => 'China'],
            ['name' => 'Guan Yu',                    'country' => 'China'],

            // India
            ['name' => 'Ashoka',                     'country' => 'India'],
            ['name' => 'Chandragupta Maurya',        'country' => 'India'],
            ['name' => 'Akbar the Great',            'country' => 'India'],
            ['name' => 'Mahatma Gandhi',             'country' => 'India'],
            ['name' => 'Srinivasa Ramanujan',        'country' => 'India'],

            // Iran
            ['name' => 'Cyrus the Great',            'country' => 'Iran'],
            ['name' => 'Darius I',                   'country' => 'Iran'],
            ['name' => 'Xerxes I',                   'country' => 'Iran'],

            // Cambodia
            ['name' => 'Jayavarman VII',             'country' => 'Cambodia'],
            ['name' => 'Norodom Sihanouk',           'country' => 'Cambodia'],

            // Indonesia
            ['name' => 'Sukarno',                    'country' => 'Indonesia'],
            ['name' => 'Suharto',                    'country' => 'Indonesia'],

            // Brazil
            ['name' => 'Pedro II',                   'country' => 'Brazil'],

            // Mexico
            ['name' => 'Moctezuma II',               'country' => 'Mexico'],
            ['name' => 'Emiliano Zapata',            'country' => 'Mexico'],

            // Peru
            ['name' => 'Pachacuti',                  'country' => 'Peru'],
            ['name' => 'Túpac Amaru II',             'country' => 'Peru'],
        ];

        foreach ($namedUsers as $data) {
            $country = $countryMap->get($data['country']);
            $citiesForCountry = $country
                ? $citiesByCountry->get($country->id, collect())
                : collect();

            $city = $citiesForCountry->isNotEmpty()
                ? $citiesForCountry->random()
                : $citiesToSeed->random();

            $rows[] = [
                'name' => $data['name'],
                'email' => self::nameToEmail($data['name']),
                'password' => $password,
                'role' => 'user',
                'locale' => $locales[array_rand($locales)],
                'theme' => $themes[array_rand($themes)],
                'city_id' => $city->id,
                'currency_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // ── Bulk anonymous users ───────────────────────────────────────────────
        $counter = 1;
        foreach ($citiesToSeed as $city) {
            for ($n = 0; $n < self::BULK_PER_CITY; $n++) {
                $tag = str_pad($counter++, 4, '0', STR_PAD_LEFT);
                $rows[] = [
                    'name' => "user{$tag}",
                    'email' => "user{$tag}@example.com",
                    'password' => $password,
                    'role' => 'user',
                    'locale' => $locales[array_rand($locales)],
                    'theme' => $themes[array_rand($themes)],
                    'city_id' => $city->id,
                    'currency_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // ── Single batched insert ──────────────────────────────────────────────
        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            DB::table('users')->insert($chunk);
        }
    }

    private static function nameToEmail(string $name): string
    {
        $ascii = Str::ascii(mb_strtolower(trim($name)));
        $slug = preg_replace('/[^a-z0-9]+/', '.', $ascii);

        return trim($slug, '.').'@example.com';
    }
}
