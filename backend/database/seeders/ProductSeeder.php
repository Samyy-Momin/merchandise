<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create();

        $catalog = [
            'Stationery' => [
                'Ballpoint Pens', 'Gel Pens', 'Highlighters', 'A4 Notebooks', 'Sticky Notes',
                'Binder Clips', 'Paper Clips', 'Staplers', 'Correction Tape', 'Erasers',
            ],
            'Housekeeping' => [
                'Microfiber Cloths', 'Dish Sponges', 'All-Purpose Cleaner', 'Glass Cleaner', 'Floor Mop',
                'Garbage Bags', 'Air Freshener', 'Toilet Cleaner', 'Scrub Brush', 'Hand Soap',
            ],
            'IT Accessories' => [
                'USB-C Cable', 'HDMI Cable', 'Wireless Mouse', 'Mechanical Keyboard', 'USB Hub',
                'Webcam', 'Laptop Stand', 'Mouse Pad', 'External SSD', 'Ethernet Cable',
            ],
            'Office Supplies' => [
                'Printer Paper (A4)', 'Whiteboard Markers', 'File Folders', 'Desk Organizer', 'Staple Remover',
                'Packing Tape', 'Paper Shredder', 'Clipboards', 'Desk Lamp', 'Calculator',
            ],
        ];

        $placeholder = 'https://via.placeholder.com/300';

        foreach (Category::all() as $category) {
            $names = $catalog[$category->name] ?? [];
            if (empty($names)) {
                // Fallback: generic names
                $names = [$category->name.' Item'];
            }

            $count = random_int(5, 10);
            // Shuffle and take a subset
            shuffle($names);
            $selected = array_slice($names, 0, $count);

            foreach ($selected as $baseName) {
                $suffix = $faker->randomElement(['', '', ' - Pack of 5', ' - Pack of 10', ' - Premium', ' - Economy']);
                $name = trim($baseName . $suffix);

                Product::create([
                    'name' => $name,
                    'description' => $faker->sentence(10),
                    'price' => $faker->numberBetween(100, 5000),
                    'category_id' => $category->id,
                    'image_url' => $placeholder,
                    'stock' => $faker->numberBetween(10, 100),
                ]);
            }
        }
    }
}

