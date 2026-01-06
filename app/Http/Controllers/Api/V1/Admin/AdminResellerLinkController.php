<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Admin\AdminResellerLinkRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminResellerLinkController extends Controller
{
    protected $linkRepo;

    public function __construct(AdminResellerLinkRepository $linkRepo)
    {
        $this->middleware(['auth:sanctum', 'role:superadministrator']);
        $this->linkRepo = $linkRepo;
    }

    public function index()
    {
        $links = $this->linkRepo->getAllLinks();
        return response()->json($links);
    }

    public function show($id)
    {
        $link = $this->linkRepo->getLinkById($id);
        return response()->json($link);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'product_id' => 'required|integer|exists:products,id',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'unique_link' => 'sometimes|string|unique:reseller_links',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $link = $this->linkRepo->createLink($request->all());
        return response()->json($link, 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'commission_rate' => 'sometimes|numeric|min:0|max:100',
            'unique_link' => 'sometimes|string|unique:reseller_links,unique_link,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $link = $this->linkRepo->updateLink($id, $request->all());
        return response()->json($link);
    }

    public function destroy($id)
    {
        $this->linkRepo->deleteLink($id);
        return response()->json(['message' => 'Reseller link deleted']);
    }

    public function getByUser($userId)
    {
        $links = $this->linkRepo->getLinksByUser($userId);
        return response()->json($links);
    }

    public function getByProduct($productId)
    {
        $links = $this->linkRepo->getLinksByProduct($productId);
        return response()->json($links);
    }
}