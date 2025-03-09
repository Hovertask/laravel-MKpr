<?php

namespace App\Repository;

use App\Models\Product;

interface IWishlistRepository {
    public function index(int $userId);
    public function add(Product $product, int $userId);
    public function remove(Product $product, int $userId);
    public function getCartItems(int $userId);
}