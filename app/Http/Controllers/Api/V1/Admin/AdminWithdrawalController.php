<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Admin\AdminWithdrawalRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminWithdrawalController extends Controller
{
    protected $withdrawalRepo;

    public function __construct(AdminWithdrawalRepository $withdrawalRepo)
    {
        $this->middleware(['auth:sanctum', 'role:superadministrator']);
        $this->withdrawalRepo = $withdrawalRepo;
    }

    public function index()
    {
        $withdrawals = $this->withdrawalRepo->getAllWithdrawals();
        return response()->json($withdrawals);
    }

    public function show($id)
    {
        $withdrawal = $this->withdrawalRepo->getWithdrawalById($id);
        return response()->json($withdrawal);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string',
            'amount' => 'sometimes|numeric',
            'account_number' => 'sometimes|string',
            'bank_code' => 'sometimes|string',
            'account_name' => 'sometimes|string',
            'paystack_reference' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $withdrawal = $this->withdrawalRepo->updateWithdrawal($id, $request->all());
        return response()->json($withdrawal);
    }

    public function destroy($id)
    {
        $this->withdrawalRepo->deleteWithdrawal($id);
        return response()->json(['message' => 'Withdrawal deleted']);
    }

    public function getByStatus($status)
    {
        $withdrawals = $this->withdrawalRepo->getWithdrawalsByStatus($status);
        return response()->json($withdrawals);
    }

    public function getByUser($userId)
    {
        $withdrawals = $this->withdrawalRepo->getWithdrawalsByUser($userId);
        return response()->json($withdrawals);
    }
}