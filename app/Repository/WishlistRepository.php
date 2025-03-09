<?php

namespace App\Repository;

use App\Models\Product;
use App\Models\Wishlist;
use App\Repository\IWishlistRepository;

class WishlistRepository implements IWishlistRepository
{
    public function index(int $userId)
    {
        try {
            return Wishlist::with('product')
                          ->where('user_id', $userId)
                          ->get();
        } catch (Exception $e) {
            throw new Exception("Failed to fetch wishlist: " . $e->getMessage());
        }
    }
    public function add(Product $product, int $userId)
    {
        try {
            $wishlist = Wishlist::updateOrCreate([
                'user_id' => $userId,
                'product_id' => $product->id,
            ]);
            return $wishlist;
        } catch (Exception $e) {
            throw new Exception("Failed to add product to wishlist: " . $e->getMessage());
        }
    }


    public function remove(Product $product, int $userId)
    {
        try {
            $deleted = Wishlist::where('user_id', $userId)
                               ->where('product_id', $product->id)
                               ->delete();
            return $deleted > 0;
        } catch (Exception $e) {
            throw new Exception("Failed to remove product from wishlist: " . $e->getMessage());
        }
    }

    public function getCartItems(int $userId)
    {
        try {
            return Cart::with('product')
                       ->where('user_id', $userId)
                       ->get();
        } catch (Exception $e) {
            throw new Exception("Failed to fetch cart items: " . $e->getMessage());
        }
    }
}