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
            ['slug' => 'phones_and_tablets', 'name' => 'Phones and Tablets'],
            ['slug' => 'health_and_beauty', 'name' => 'Health and Beauty'],
            ['slug' => 'computing', 'name' => 'Computing'],
            ['slug' => 'home_and_office', 'name' => 'Home and Office'],
            ['slug' => 'fashion', 'name' => 'Fashion'],
            ['slug' => 'electronic', 'name' => 'Electronic'],
            ['slug' => 'baby_products', 'name' => 'Baby Products'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name']]
            );
        }
    }
}
