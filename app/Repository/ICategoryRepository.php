<?php
namespace App\Repository;

use App\Models\Category;
use App\Repository\ICategoryRepository;

interface ICategoryRepository
{
    public function showAll();
    public function create($data, $slug);
}