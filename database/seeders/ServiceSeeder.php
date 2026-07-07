<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'Wash & Iron',
                'slug' => 'wash-and-iron',
                'description' => 'Your everyday clothes deserve more than a quick spin. We wash with premium detergents, iron with precision, and send everything back crisp, fresh, and ready to wear.',
                'icon' => 'shirt',
                'base_price' => 120.00,
                'price_unit' => 'per_item',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Wash Only',
                'slug' => 'wash-only',
                'description' => 'We wash, dry, and neatly fold your laundry with fabric-specific care, so all you have to do is set them in your wardrobe.',
                'icon' => 'washing-machine',
                'base_price' => 80.00,
                'price_unit' => 'per_item',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Dry Cleaning',
                'slug' => 'dry-cleaning',
                'description' => 'Suits, formal wear, delicate fabrics — these need a specialist, not a regular machine wash. Our dry cleaning service uses eco-friendly solvents and professional finishing.',
                'icon' => 'sparkles',
                'base_price' => 250.00,
                'price_unit' => 'per_item',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Steam Press',
                'slug' => 'steam-press',
                'description' => 'Sometimes clothes just need a refresh, not a full clean. Our steam pressing service removes every wrinkle without touching the fibres.',
                'icon' => 'wind',
                'base_price' => 60.00,
                'price_unit' => 'per_item',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Carpet Cleaning',
                'slug' => 'carpet-cleaning',
                'description' => 'Our professional carpet and rug cleaning service does the deep work, removing stubborn stains and eliminating odours from all types of carpets.',
                'icon' => 'home',
                'base_price' => 500.00,
                'price_unit' => 'per_kg',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Blankets & Comforters',
                'slug' => 'blankets-comforters',
                'description' => 'We handle blankets, comforters, and quilts with proper care, getting rid of dust mites, allergens, and stains that regular washing just doesn\'t reach.',
                'icon' => 'bed',
                'base_price' => 350.00,
                'price_unit' => 'per_item',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Curtain Cleaning',
                'slug' => 'curtain-cleaning',
                'description' => 'Our professional curtain cleaning service removes dust, allergens, and cooking odours from living room curtains, shears, net curtains, and luxury drapes.',
                'icon' => 'blinds',
                'base_price' => 200.00,
                'price_unit' => 'per_kg',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Shoes Cleaning',
                'slug' => 'shoes-cleaning',
                'description' => 'Whether it\'s sneakers, leather shoes, boots, or formal footwear — our shoe cleaning service brings them back to life.',
                'icon' => 'footprints',
                'base_price' => 300.00,
                'price_unit' => 'per_item',
                'is_active' => true,
                'sort_order' => 8,
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}