<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Repository\ICartRepository;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected $cartRepository;

    public function __construct(ICartRepository $cartRepository)
    {
        $this->cartRepository = $cartRepository;
    }

    public function addToCart(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => 'nullable|integer|min:1',
        ]);

        $quantity = $request->input('quantity', 1);
        $userId = Auth::id();

        try {
            $cart = $this->cartRepository->addToCart($product, $userId, $quantity);
            return response()->json(['message' => 'Product added to cart!', 'cart' => $cart], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function removeFromCart(Product $product)
    {
        $userId = Auth::id();

        try {
            $this->cartRepository->removeFromCart($product, $userId);
            return response()->json(['message' => 'Product removed from cart!'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getCartItems()
    {
        $userId = Auth::id();

        try {
            $cartItems = $this->cartRepository->getCartItems($userId);
            return response()->json(['cart_items' => $cartItems], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
