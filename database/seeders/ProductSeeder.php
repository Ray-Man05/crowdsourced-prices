<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $kg   = Unit::where('symbol', 'kg')->first();
        $g    = Unit::where('symbol', 'g')->first();
        $l    = Unit::where('symbol', 'L')->first();
        $pc   = Unit::where('symbol', 'pc')->first();

        // [category name => [[name, description, unit]]]
        $products = [
            'Fruits' => [
                [['en' => 'Banana',     'fr' => 'Banane'],      ['en' => 'Common yellow banana.',      'fr' => 'Banane jaune commune.'],      $kg],
                [['en' => 'Apple',      'fr' => 'Pomme'],       ['en' => 'Red or green apple.',        'fr' => 'Pomme rouge ou verte.'],      $kg],
                [['en' => 'Orange',     'fr' => 'Orange'],      ['en' => 'Fresh orange.',              'fr' => 'Orange fraîche.'],            $kg],
                [['en' => 'Strawberry', 'fr' => 'Fraise'],      ['en' => 'Fresh strawberries.',        'fr' => 'Fraises fraîches.'],          $kg],
                [['en' => 'Mango',      'fr' => 'Mangue'],      ['en' => 'Tropical mango.',            'fr' => 'Mangue tropicale.'],          $kg],
            ],
            'Vegetables' => [
                [['en' => 'Tomato',     'fr' => 'Tomate'],      ['en' => 'Fresh tomatoes.',             'fr' => 'Tomates fraîches.'],          $kg],
                [['en' => 'Potato',     'fr' => 'Pomme de terre'], ['en' => 'White potato.',           'fr' => 'Pomme de terre blanche.'],     $kg],
                [['en' => 'Onion',      'fr' => 'Oignon'],      ['en' => 'Yellow onion.',               'fr' => 'Oignon jaune.'],              $kg],
                [['en' => 'Carrot',     'fr' => 'Carotte'],     ['en' => 'Fresh carrots.',              'fr' => 'Carottes fraîches.'],         $kg],
                [['en' => 'Spinach',    'fr' => 'Épinard'],     ['en' => 'Fresh spinach leaves.',       'fr' => 'Feuilles d\'épinard fraîches.'], $kg],
            ],
            'Dairy and Eggs' => [
                [['en' => 'Whole Milk',     'fr' => 'Lait entier'], ['en' => 'Fresh whole milk.',         'fr' => 'Lait entier frais.'],         $l],
                [['en' => 'Butter',         'fr' => 'Beurre'],      ['en' => 'Unsalted butter.',          'fr' => 'Beurre non salé.'],           $kg],
                [['en' => 'Cheddar Cheese', 'fr' => 'Cheddar'],     ['en' => 'Aged cheddar cheese.',      'fr' => 'Cheddar affiné.'],            $kg],
                [['en' => 'Yogurt',         'fr' => 'Yaourt'],      ['en' => 'Plain whole-milk yogurt.',  'fr' => 'Yaourt nature au lait entier.'], $kg],
                [['en' => 'Eggs',           'fr' => 'Œufs'],        ['en' => 'Free-range eggs.',          'fr' => 'Œufs de poules élevées en plein air.'], $pc],
            ],
            'Meat and Poultry' => [
                [['en' => 'Chicken Breast', 'fr' => 'Blanc de poulet'], ['en' => 'Boneless chicken breast.', 'fr' => 'Blanc de poulet désossé.'],   $kg],
                [['en' => 'Ground Beef',    'fr' => 'Bœuf haché'],      ['en' => 'Lean ground beef.',        'fr' => 'Bœuf haché maigre.'],         $kg],
                [['en' => 'Lamb Chops',     'fr' => 'Côtelettes d\'agneau'], ['en' => 'Fresh lamb chops.',  'fr' => 'Côtelettes d\'agneau fraîches.'], $kg],
                [['en' => 'Pork Ribs',      'fr' => 'Travers de porc'], ['en' => 'Pork spare ribs.',         'fr' => 'Travers de porc.'],           $kg],
                [['en' => 'Turkey',         'fr' => 'Dinde'],           ['en' => 'Whole turkey.',            'fr' => 'Dinde entière.'],             $kg],
            ],
            'Herbs and Spices' => [
                [['en' => 'Black Pepper', 'fr' => 'Poivre noir'], ['en' => 'Ground black pepper.',         'fr' => 'Poivre noir moulu.'],         $kg],
                [['en' => 'Cumin',        'fr' => 'Cumin'],       ['en' => 'Ground cumin.',                'fr' => 'Cumin moulu.'],               $kg],
                [['en' => 'Paprika',      'fr' => 'Paprika'],     ['en' => 'Sweet paprika powder.',        'fr' => 'Paprika doux en poudre.'],    $kg],
                [['en' => 'Cinnamon',     'fr' => 'Cannelle'],    ['en' => 'Ground cinnamon.',             'fr' => 'Cannelle moulue.'],           $kg],
                [['en' => 'Turmeric',     'fr' => 'Curcuma'],     ['en' => 'Ground turmeric.',             'fr' => 'Curcuma moulu.'],             $kg],
            ],
            'Condiments' => [
                [['en' => 'Olive Oil',  'fr' => 'Huile d\'olive'], ['en' => 'Extra virgin olive oil.',     'fr' => 'Huile d\'olive extra vierge.'], $l],
                [['en' => 'Ketchup',    'fr' => 'Ketchup'],        ['en' => 'Tomato ketchup.',             'fr' => 'Ketchup à la tomate.'],       $kg],
                [['en' => 'Mustard',    'fr' => 'Moutarde'],       ['en' => 'Dijon mustard.',              'fr' => 'Moutarde de Dijon.'],         $kg],
                [['en' => 'Soy Sauce',  'fr' => 'Sauce soja'],     ['en' => 'Dark soy sauce.',             'fr' => 'Sauce soja foncée.'],         $l],
                [['en' => 'Honey',      'fr' => 'Miel'],           ['en' => 'Raw honey.',                  'fr' => 'Miel brut.'],                 $kg],
            ],

        ];

        foreach ($products as $categoryName => $items) {
            $category = Category::where('name->en', $categoryName)->first();
            foreach ($items as [$name, $description, $unit]) {
                Product::create([
                    'name'        => $name,
                    'description' => $description,
                    'category_id' => $category->id,
                    'unit_id'     => $unit->id,
                ]);
            }
        }
    }
}