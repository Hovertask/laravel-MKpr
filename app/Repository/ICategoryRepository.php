<?php
namespace App\Repository;

use App\Models\Category;
use App\Repository\ICategoryRepository;

interface ICategoryRepository
{
    public function showAll(Category $category);
    public function create($data);
}