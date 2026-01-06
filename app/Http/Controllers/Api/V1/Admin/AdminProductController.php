<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Admin\AdminProductRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminProductController extends Controller
{
    protected $productRepo;

    public function __construct(AdminProductRepository $productRepo)
    {
        $this->middleware(['auth:sanctum', 'role:superadministrator']);
        $this->productRepo = $productRepo;
    }

    public function index()
    {
        $products = $this->productRepo->getAllProducts();
        return response()->json($products);
    }

    public function show($id)
    {
        $product = $this->productRepo->getProductById($id);
        return response()->json($product);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'user_id' => 'required|integer|exists:users,id',
            'category_id' => 'required|integer|exists:categories,id',
            'description' => 'required|string',
            'stock' => 'required|integer',
            'price' => 'required|numeric',
            'currency' => 'required|string',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'meet_up_preference' => 'nullable|string',
            'delivery_fee' => 'nullable|numeric|min:0',
            'estimated_delivery_date' => 'nullable|string',
            'phone_number' => 'required|string',
            'email' => 'nullable|email',
            'social_media_link' => 'nullable|string',
            'image' => 'nullable|string',
            'resell_budget' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = $this->productRepo->createProduct($request->all());
        return response()->json($product, 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string',
            'category_id' => 'sometimes|integer|exists:categories,id',
            'description' => 'sometimes|string',
            'stock' => 'sometimes|integer',
            'price' => 'sometimes|numeric',
            'currency' => 'sometimes|string',
            'discount' => 'sometimes|numeric|min:0',
            'payment_method' => 'sometimes|string',
            'meet_up_preference' => 'sometimes|string',
            'delivery_fee' => 'sometimes|numeric|min:0',
            'estimated_delivery_date' => 'sometimes|string',
            'phone_number' => 'sometimes|string',
            'email' => 'sometimes|email',
            'social_media_link' => 'sometimes|string',
            'image' => 'sometimes|string',
            'resell_budget' => 'sometimes|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = $this->productRepo->updateProduct($id, $request->all());
        return response()->json($product);
    }

    public function destroy($id)
    {
        $this->productRepo->deleteProduct($id);
        return response()->json(['message' => 'Product deleted']);
    }

    public function getByCategory($categoryId)
    {
        $products = $this->productRepo->getProductsByCategory($categoryId);
        return response()->json($products);
    }

    public function getByUser($userId)
    {
        $products = $this->productRepo->getProductsByUser($userId);
        return response()->json($products);
    }
}