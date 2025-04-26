<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\ICategoryRepository;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    protected $category;

    public function __construct(ICategoryRepository $category)
    {
        $this->category = $category;
    }

    public function index()
    {
        return $this->category->showAll();
    }

    public function create(Request $request)
    {
        $validateCategory = Validator::make($request->all(), [
            'name' => 'required|string',
        ]);

        $slug = Str::slug($request->name);

        if ($validateCategory->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validateCategory->errors(),
            ], 422);
        }

        $category = $this->category->create($validateCategory->validated(), $slug);
        return response()->json([
            'status' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }
}
