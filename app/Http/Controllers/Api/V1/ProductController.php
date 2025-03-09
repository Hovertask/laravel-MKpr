<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use App\Models\ResellerLink;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\ProductRepository;
use Illuminate\Support\Facades\Validator;
use App\Repository\ITrendingProductRepository;

class ProductController extends Controller
{
    protected $product;
    

    public function __construct(ProductRepository $product)
    {
        $this->product = $product;
    }

    public function index()
    {
        return $this->product->showAll(Product::class);
    }

    public function store(Request $request)
    {
        $validateProduct = Validator::make($request->all(), [
            'name' => 'required|string',
            'user_id' => 'required|integer|exists:users,id',
            'description' => 'required|string',
            'stock' => 'required|integer',
            'price' => 'required|integer',
            //'image' => 'required|string|',
            'category_id' => 'required|integer',
        ]);

        if ($validateProduct->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validateProduct->errors(),
            ], 422); 
        }

        $product = $this->product->create($validateProduct->validated());

        //at this point we may create a email and notification for both admin and user

        return response()->json([
            'status' => true,
            'message' => 'Product created successfully',
            'data' => $product,
        ]);
    }

    public function update($id, Request $request)
    {
        $validate = Validator::make($request->all(),[
            'name' => 'required|string|max:255',
            'user_id' => 'required|integer|exists:users,id',
            'category_id' => 'required|integer|exists:categories,id',
            'name' => 'required|string',
            'description' => 'required|string',
            'stock' => 'required|integer',
            'price' => 'required|integer',
            //'image' => 'required|string',

        ]);

        if ($validate->fails()) {
            return response()->json([            
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validate->errors(),
            ], 422);
        }

        $product = $this->product->update($id, $validate->validated());

        return response()->json([
            'status' => true,
            'message' => 'Product updated successfully',
            'data' => $product,
        ]);
    }

    // public function destroy($id)
    // {
    //     $product = $this->product->show(Product::class, $id);
    //     $this->product->delete($product);
    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Product deleted successfully',
    //     ]);
    // }

    // public function show($id)
    // {
    //     return $this->product->show($id);
    // }
    public function show(Request $request, $id, ITrendingProductRepository $trendingProduct)
    {
        $resellerId = $request->query('reseller', null);

        try {

            if (!Product::find($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product does not exist',
                ], 404);
            }
            
            $trendingProduct->incrementViewCount($id);
            $product = $this->product->show($id, $resellerId);

            return response()->json([
                'success' => true,
                'product' => $product,
                'reseller' => $resellerId
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }
    }
    

    public function approveProduct($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'status' => 'required|string'
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validate->errors(),
            ], 422);
        }

        $product = $this->product->approveProduct($id, $request);

        return response()->json([
            'status' => true,
            'message' => 'Product approved successfully',
            'data' => $product,
        ]);
        
    }

    public function showAll(Product $product)
    {
        return $this->product->showAll($product);
    }

    public function resellerLink($id)
    {

        $validate = Validator::make(['id' => $id], [
            'id' => 'required|integer|exists:products,id'
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'This product ID does not exist',
                'errors' => $validate->errors(),
            ], 422);
        }

        return $this->product->resellerLink($id);
    }

    public function productByLocation($location)
    {
        return $this->product->productByLocation($location);
    }
}
