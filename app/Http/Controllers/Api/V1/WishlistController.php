<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repository\IWishlistRepository;

class WishlistController extends Controller
{
    protected $wishlistRepository;

    public function __construct(IWishlistRepository $wishlistRepository)
    {
        $this->wishlistRepository = $wishlistRepository;
    }
    public function index()
    {
        return $this->wishlistRepository->index(Auth::id());
    }

    public function add(Product $product)
    {
        try {
            $this->wishlistRepository->add($product, Auth::id());
            return response()->json(['success' => true, 'message' => 'Product added to wishlist!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function remove(Product $product)
    {
        try {
            $this->wishlistRepository->remove($product, Auth::id());
            return response()->json(['success' => true, 'message' => 'Product removed from wishlist!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

}
