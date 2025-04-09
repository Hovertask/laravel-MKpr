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

    public function create($data)
    {
        return Category::create($data);
    }

}