<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Admin\AdminCategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminCategoryController extends Controller
{
    protected $categoryRepo;

    public function __construct(AdminCategoryRepository $categoryRepo)
    {
        $this->middleware(['auth:sanctum', 'role:superadministrator']);
        $this->categoryRepo = $categoryRepo;
    }

    public function index()
    {
        $categories = $this->categoryRepo->getAllCategories();
        return response()->json($categories);
    }

    public function show($id)
    {
        $category = $this->categoryRepo->getCategoryById($id);
        return response()->json($category);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'slug' => 'required|string|unique:categories',
            'parent_id' => 'nullable|integer|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $category = $this->categoryRepo->createCategory($request->all());
        return response()->json($category, 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string',
            'slug' => 'sometimes|string|unique:categories,slug,' . $id,
            'parent_id' => 'sometimes|nullable|integer|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $category = $this->categoryRepo->updateCategory($id, $request->all());
        return response()->json($category);
    }

    public function destroy($id)
    {
        $this->categoryRepo->deleteCategory($id);
        return response()->json(['message' => 'Category deleted']);
    }

    public function getByParent($parentId)
    {
        $categories = $this->categoryRepo->getCategoriesByParent($parentId);
        return response()->json($categories);
    }
}