<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Admin\AdminAdvertiseRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminAdvertiseController extends Controller
{
    protected $advertiseRepo;

    public function __construct(AdminAdvertiseRepository $advertiseRepo)
    {
        $this->middleware(['auth:sanctum', 'role:superadministrator']);
        $this->advertiseRepo = $advertiseRepo;
    }

    public function index()
    {
        $advertises = $this->advertiseRepo->getAllAdvertises();
        return response()->json($advertises);
    }

    public function show($id)
    {
        $advertise = $this->advertiseRepo->getAdvertiseById($id);
        return response()->json($advertise);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string',
            'platforms' => 'sometimes|array',
            'gender' => 'sometimes|string',
            'religion' => 'sometimes|array',
            'location' => 'sometimes|array',
            'no_of_status_post' => 'sometimes|integer',
            'payment_method' => 'sometimes|string',
            'description' => 'sometimes|string',
            'number_of_participants' => 'sometimes|integer',
            'payment_per_task' => 'sometimes|numeric',
            'estimated_cost' => 'sometimes|numeric',
            'status' => 'sometimes|string',
            'deadline' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $advertise = $this->advertiseRepo->updateAdvertise($id, $request->all());
        return response()->json($advertise);
    }

    public function destroy($id)
    {
        $this->advertiseRepo->deleteAdvertise($id);
        return response()->json(['message' => 'Advertise deleted']);
    }

    public function approve($id)
    {
        $advertise = $this->advertiseRepo->approveAdvertise($id);
        return response()->json($advertise);
    }

    public function reject($id)
    {
        $advertise = $this->advertiseRepo->rejectAdvertise($id);
        return response()->json($advertise);
    }

    public function getByStatus($status)
    {
        $advertises = $this->advertiseRepo->getAdvertisesByStatus($status);
        return response()->json($advertises);
    }

    public function getByUser($userId)
    {
        $advertises = $this->advertiseRepo->getAdvertisesByUser($userId);
        return response()->json($advertises);
    }
}