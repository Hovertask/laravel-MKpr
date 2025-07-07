<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['slug' => 'all', 'name' => 'all'],
            ['slug' => 'phones_and_tablets', 'name' => 'phones and tablets'],
            ['slug' => 'health_and_beauty', 'name' => 'health and beauty'],
            ['slug' => 'computing', 'name' => 'computing'],
            ['slug' => 'home_and_office', 'name' => 'home and office'],
            ['slug' => 'fashion', 'name' => 'fashion'],
            ['slug' => 'electronic', 'name' => 'electronic'],
            ['slug' => 'baby_products', 'name' => 'baby products'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name']]
            );
        }
    }
}
