<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use App\Models\ResellerLink;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\ProductRepository;
use Illuminate\Support\Facades\Validator;
use App\Repository\ITrendingProductRepository;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;

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
        // Validate the request
        $validateProduct = Validator::make($request->all(), [
            'name' => 'required|string',
            'user_id' => 'required|integer|exists:users,id',
            'category_id' => 'required|integer',
            'description' => 'required|string',
            'stock' => 'required|integer',
            'price' => 'required|numeric',
            'currency' => 'required|string',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:255',
            'meet_up_preference' => 'nullable|string|max:255',
            'delivery_fee' => 'nullable|numeric|min:0',
            'estimated_delivery_date' => 'nullable|date',
            'phone_number' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255',
            'social_media_link' => 'nullable|string|max:255',
            'file_path' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:10240', // Supports images & videos (max 10MB)
            'file_paths' => 'nullable|array', // Multiple files
            'file_paths.*' => 'file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:10240', // Each file validation
            'media_type' => 'nullable|string|max:255',
        ]);

        if ($validateProduct->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validateProduct->errors(),
            ], 422);
        }

        // Check video duration
        if ($request->hasFile('file_path')) {
            $file = $request->file('file_path');
            $path = $file->getPathname();

            $ffprobe = FFProbe::create();
            $duration = $ffprobe
                ->format($path)
                ->get('duration');

            if ($duration > 30) {
                return response()->json(['error' => 'The uploaded video must not exceed 30 seconds.'], 422);
            }
        }

        // Create the product using the repository
        $product = $this->product->create($validateProduct->validated(), $request);

        // At this point, you may create an email and notification for both admin and user

        return response()->json([
            'status' => true,
            'message' => 'Product created successfully',
            'data' => $product,
        ]);
    }

    public function update(Request $request, $id)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'sometimes|string',
            'user_id' => 'sometimes|integer',
            'category_id' => 'sometimes|integer',
            'description' => 'sometimes|string',
            'stock' => 'sometimes|integer',
            'price' => 'sometimes|numeric',
            'currency' => 'sometimes|string',
            'discount' => 'sometimes|numeric',
            'payment_method' => 'sometimes|string',
            'meet_up_preference' => 'sometimes|string',
            'delivery_fee' => 'sometimes|numeric',
            'estimated_delivery_date' => 'sometimes|date',
            'phone_number' => 'sometimes|string',
            'email' => 'sometimes|email',
            'social_media_link' => 'sometimes|url',
            'file_path' => 'sometimes|file|mimes:jpeg,png,jpg,gif|max:2048',
            'file_paths.*' => 'sometimes|file|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $product = $this->product->update($validatedData, $request, $id);

        return response()->json([
            'status' => true,
            'message' => 'Product updated successfully',
            'data' => $product,
        ]);
    }

    public function destroy($id)
    {
        $this->product->delete($id);

        return response()->json([
            'message' => 'Product deleted successfully.',
        ], 200);
    }

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
