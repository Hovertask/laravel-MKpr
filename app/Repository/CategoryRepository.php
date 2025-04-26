<?php
namespace App\Repository;

use App\Models\Category;
use App\Repository\ICategoryRepository;

class CategoryRepository implements ICategoryRepository
{
    public function showAll()
    {
        return Category::all();
    }

    public function create($data, $slug)
    {
        //dd($slug);
        return Category::create([
            'name' => $data['name'],
            'slug' => $slug
        ]);
    }

}