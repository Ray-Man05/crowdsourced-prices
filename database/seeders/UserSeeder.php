<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $cities      = City::all();
        $currencies  = Currency::all();
        $locales     = ['en', 'fr'];
        $themes      = ['light', 'dark'];

        // Admin account — fixed, easy to find in tests
        User::create([
            'name'        => 'Admin',
            'email'       => 'admin@example.com',
            'password'    => Hash::make('password'),
            'role'        => 'admin',
            'locale'      => 'en',
            'theme'       => 'light',
            'city_id'     => $cities->first()->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
        ]);

        $names = [

            // United States
            'Benjamin Franklin',
            'Ulysses S. Grant',
            'Frederick Douglass',
            'Harriet Tubman',
            'Abraham Lincoln',
            'Franklin Delano Roosevelt',
            'W.E.B. Du Bois',
            'Booker T. Washington',
            'Mark Twain',
            'John Fitzgerald Kennedy',
            'Nikola Tesla',
            'Martin Luther King Jr',
            'Malcolm X',
            'Elijah Muhammad',
            'Sitting Bull',
            'Ernst Hemingway',
            'Lyndon B. Johnson',
            'George Washington',
            'Thomas Jefferson',
            'John Quincy Adams',
            'Andrew Jackson',
            'Stonewall Jackson',
            'Robert E. Lee',
            'Jefferson Davis',
            'George B. McClellan',
            'Benjamin Butler',
            'William Tecumseh Sherman',

            // France
            'Joan of Arc',
            'Napoleon Bonaparte',
            'Voltaire',
            'René Descartes',
            'Albert Camus',
            'Montesquieu',
            'Louis XIV',
            'Cardinal Richelieu',
            'Maximillien Robespierre',
            'Victor Hugo',
            'Guy de Maupassant',
            'Emile Zola',
            'Hector Malot',

            // Germany
            'Johann Wolfgang von Goethe',
            'Immanuel Kant',
            'Otto von Bismarck',
            'Johannes Gutenberg',
            'Albert Einstein',
            'Martin Luther',
            'Max Planck',
            'John Paul II',

            // Spain
            'Miguel de Cervantes',
            'Isabella I of Castile',
            'Ferdinand II of Aragon',
            'El Cid',
            'Francisco Goya',
            'Hernán Cortés',
            'Pablo Picasso',

            // Portugal
            'Vasco da Gama',
            'Prince Henry the Navigator',
            'Luís de Camões',
            'Afonso I of Portugal',
            'Marquis of Pombal',
            'Fernando Pessoa',

            // Italy
            'Julius Caesar',
            'Augustus',
            'Numa Pompilius',
            'Dante Alighieri',
            'Niccolò Machiavelli',
            'Leonardo da Vinci',
            'Michelangelo',
            'Galileo Galilei',
            'Marco Polo',
            'Romulus',
            'Giovanni Boccaccio',
            'Lorenzo de Medici',
            'Giuseppe Garibaldi',
            'Cicero',
            'Catilinia',
            'Scipio Africanus',
            'Lucius Tarquinius Superbus',
            'Nerva',
            'Trajan',
            'Hadrian',
            'Antoninus Pius',
            'Marcus Aurelius',


            

            // Greece
            'Socrates',
            'Plato',
            'Aristotle',
            'Alexander the Great',
            'Pericles',
            'Herodotus',
            'Archimedes',
            'Pythagoras',
            

            // Netherlands
            'Erasmus of Rotterdam',
            'Rembrandt van Rijn',
            'Baruch Spinoza',
            'William of Orange',
            'Christiaan Huygens',
            'Antonie van Leeuwenhoek',

            // Belgium
            'Hergé ',
            'Leopold II',

            // Morocco
            'Ibn Battuta',
            'Muhammad al-Idrisi',
            'Ibn Khaldun',
            'Ahmad al-Mansur',
            'Yusuf ibn Tashfin',
            'Fatima al-Fihri',

            // China
            'Confucius',
            'Laozi',
            'Han Feizi',
            'Sun Tzu',
            'Qin Shi Huang',
            'Wu Zetian',
            'Zhang Qian',
            'Zheng He',
            'Li Bei',
            'Guan Yu',
            'Zhang Fei',
            'He Jin',
            'Dong Zhuo',
            'Cao Cao',
            'Lü Bu',

            'Ogedei Khan',
            

            // India
            'Ashoka',
            'Chandragupta Maurya',
            'Akbar the Great',
            'Chanakya',
            'Aryabhata',
            'Bhaskara II',
            'Rabindranath Tagore',
            'Mahatma Gandhi',
            'Srinivasa Ramanujan Iyengar',

            // South Africa
            'Shaka Zulu',
            'Nelson Mandela',
            'Desmond Tutu',
            'Steve Biko',
            'Paul Kruger',
            'Jan van Riebeeck',

            // Egypt
            'Cleopatra VII',
            'Ramses II',
            'Akhenaten',
            'Tutankhamun',
            'Imhotep',
            'Amenhotep',
            'Saladin',
            'Muhammad Ali Pasha',
            'Gamal Abdel Nasser',
            'Boutros Boutros Ghali',
            "Ramses",
            "Moses",
            "Hatshepsut",
            
            // Austria
            "Wolfgang Amadeus Mozart",
            "Christoph Walz",
            "Arnold Schwarzenegger",

            // Algeria
            "Augustine of Hippo",
            
        ];

        foreach ($names as $name) {
            $email = strtolower(str_replace(' ', '.', $name)) . '@example.com';

            User::create([
                'name'        => $name,
                'email'       => $email,
                'password'    => Hash::make('password'),
                'role'        => 'user',
                'locale'      => $locales[array_rand($locales)],
                'theme'       => $themes[array_rand($themes)],
                'city_id'     => $cities->random()->id,
                'currency_id' => $currencies->random()->id,
            ]);
        }
    }
}