<?php

namespace App\Repository\Admin;

interface IAdminCategoryRepository
{
    public function getAllCategories();
    public function getCategoryById($id);
    public function updateCategory($id, array $data);
    public function deleteCategory($id);
    public function createCategory(array $data);
    public function getCategoriesByParent($parentId);
}