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
    ['slug' => 'all', 'name' => 'All'],
    ['slug' => 'phones_and_tablets', 'name' => 'Phones and Tablets'],
    ['slug' => 'health_and_beauty', 'name' => 'Health and Beauty'],
    ['slug' => 'computing', 'name' => 'Computing'],
    ['slug' => 'home_and_office', 'name' => 'Home and Office'],
    ['slug' => 'fashion', 'name' => 'Fashion'],
    ['slug' => 'electronics', 'name' => 'Electronics'],
    ['slug' => 'baby_products', 'name' => 'Baby Products'],
    ['slug' => 'gaming', 'name' => 'Gaming'],
    ['slug' => 'sports_and_fitness', 'name' => 'Sports & Fitness'],
    ['slug' => 'automobile', 'name' => 'Automobile'],
    ['slug' => 'groceries', 'name' => 'Groceries'],
    ['slug' => 'kitchen_and_appliances', 'name' => 'Kitchen & Appliances'],
    ['slug' => 'furniture', 'name' => 'Furniture'],
    ['slug' => 'books_and_stationery', 'name' => 'Books & Stationery'],
    ['slug' => 'pet_supplies', 'name' => 'Pet Supplies'],
    ['slug' => 'tools_and_hardware', 'name' => 'Tools & Hardware'],
    ['slug' => 'jewelry_and_watches', 'name' => 'Jewelry & Watches'],
    ['slug' => 'music_and_audio', 'name' => 'Music & Audio'],
    ['slug' => 'camera_and_photography', 'name' => 'Camera & Photography'],
    ['slug' => 'industrial_and_scientific', 'name' => 'Industrial & Scientific'],
];


        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name']]
            );
        }
    }
}
