<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Admin\AdminResellerConversionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminResellerConversionController extends Controller
{
    protected $conversionRepo;

    public function __construct(AdminResellerConversionRepository $conversionRepo)
    {
        $this->middleware(['auth:sanctum', 'role:superadministrator']);
        $this->conversionRepo = $conversionRepo;
    }

    public function index()
    {
        $conversions = $this->conversionRepo->getAllConversions();
        return response()->json($conversions);
    }

    public function show($id)
    {
        $conversion = $this->conversionRepo->getConversionById($id);
        return response()->json($conversion);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reseller_code' => 'sometimes|string',
            'visitor_cookie' => 'sometimes|string',
            'ip' => 'sometimes|string',
            'user_agent' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $conversion = $this->conversionRepo->updateConversion($id, $request->all());
        return response()->json($conversion);
    }

    public function destroy($id)
    {
        $this->conversionRepo->deleteConversion($id);
        return response()->json(['message' => 'Reseller conversion deleted']);
    }

    public function getByReseller($resellerId)
    {
        $conversions = $this->conversionRepo->getConversionsByReseller($resellerId);
        return response()->json($conversions);
    }

    public function getByProduct($productId)
    {
        $conversions = $this->conversionRepo->getConversionsByProduct($productId);
        return response()->json($conversions);
    }
}