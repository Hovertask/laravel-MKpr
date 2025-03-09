<?php

namespace App\Http\Controllers\Api\V1;

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
        return $this->category->showAll(Category::class);
    }

    public function create(Request $request)
    {
        $validateCategory = Validator::make($request->all(), [
            'name' => 'required|string',
        ]);

        if ($validateCategory->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validateCategory->errors(),
            ], 422);
        }

        $category = $this->category->create($validateCategory->validated());
        return response()->json([
            'status' => true,
            'message' => 'Category created successfully',
            'data' => $this->category->create([
                'name' => $request->name
            ])
        ], 201);
    }
}
