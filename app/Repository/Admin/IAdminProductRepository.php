<?php

namespace App\Repository\Admin;

interface IAdminProductRepository
{
    public function getAllProducts();
    public function getProductById($id);
    public function updateProduct($id, array $data);
    public function deleteProduct($id);
    public function createProduct(array $data);
    public function getProductsByCategory($categoryId);
    public function getProductsByUser($userId);
}