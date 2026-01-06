<?php

namespace App\Repository\Admin;

use App\Models\Category;
use App\Repository\Admin\IAdminCategoryRepository;

class AdminCategoryRepository implements IAdminCategoryRepository
{
    public function getAllCategories()
    {
        return Category::with('parent')->paginate(20);
    }

    public function getCategoryById($id)
    {
        return Category::with(['parent', 'children'])->findOrFail($id);
    }

    public function updateCategory($id, array $data)
    {
        $category = Category::findOrFail($id);
        $category->update($data);
        return $category;
    }

    public function deleteCategory($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        return true;
    }

    public function createCategory(array $data)
    {
        return Category::create($data);
    }

    public function getCategoriesByParent($parentId)
    {
        return Category::where('parent_id', $parentId)->with('parent')->paginate(20);
    }
}