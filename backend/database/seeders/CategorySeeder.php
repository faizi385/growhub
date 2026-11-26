<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Fashion & Clothing',
                'slug' => 'fashion-clothing',
                'icon' => 'shirt',
                'sort_order' => 1,
            ],
            [
                'name' => 'Watches',
                'slug' => 'watches',
                'icon' => 'watch',
                'sort_order' => 2,
            ],
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'icon' => 'smartphone',
                'sort_order' => 3,
            ],
            [
                'name' => 'Accessories',
                'slug' => 'accessories',
                'icon' => 'gem',
                'sort_order' => 4,
            ],
            [
                'name' => 'Shoes',
                'slug' => 'shoes',
                'icon' => 'footprints',
                'sort_order' => 5,
            ],
            [
                'name' => 'Beauty & Personal Care',
                'slug' => 'beauty-personal-care',
                'icon' => 'sparkles',
                'sort_order' => 6,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
