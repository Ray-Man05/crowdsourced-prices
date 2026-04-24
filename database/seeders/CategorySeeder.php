<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => ['en' => 'Fruits',           'fr' => 'Fruits'],             'color' => '#F97316'],
            ['name' => ['en' => 'Vegetables',       'fr' => 'Légumes'],            'color' => '#22C55E'],
            ['name' => ['en' => 'Dairy and Eggs',   'fr' => 'Produits laitiers'],  'color' => '#EAB308'],
            ['name' => ['en' => 'Meat and Poultry', 'fr' => 'Viandes'],            'color' => '#EF4444'],
            ['name' => ['en' => 'Herbs and Spices', 'fr' => 'Herbes et épices'],   'color' => '#A855F7'],
            ['name' => ['en' => 'Condiments',       'fr' => 'Condiments'],         'color' => '#EC4899'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}