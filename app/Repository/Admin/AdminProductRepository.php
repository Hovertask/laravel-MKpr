<?php

namespace App\Repository\Admin;

use App\Models\Product;
use App\Repository\Admin\IAdminProductRepository;

class AdminProductRepository implements IAdminProductRepository
{
    public function getAllProducts()
    {
        return Product::with(['user', 'category'])->paginate(20);
    }

    public function getProductById($id)
    {
        return Product::with(['user', 'category', 'reviews'])->findOrFail($id);
    }

    public function updateProduct($id, array $data)
    {
        $product = Product::findOrFail($id);
        $product->update($data);
        return $product;
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return true;
    }

    public function createProduct(array $data)
    {
        return Product::create($data);
    }

    public function getProductsByCategory($categoryId)
    {
        return Product::where('category_id', $categoryId)->with(['user', 'category'])->paginate(20);
    }

    public function getProductsByUser($userId)
    {
        return Product::where('user_id', $userId)->with(['user', 'category'])->paginate(20);
    }
}