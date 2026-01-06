<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Admin\AdminTransactionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminTransactionController extends Controller
{
    protected $transactionRepo;

    public function __construct(AdminTransactionRepository $transactionRepo)
    {
        $this->middleware(['auth:sanctum', 'role:superadministrator']);
        $this->transactionRepo = $transactionRepo;
    }

    public function index()
    {
        $transactions = $this->transactionRepo->getAllTransactions();
        return response()->json($transactions);
    }

    public function show($id)
    {
        $transaction = $this->transactionRepo->getTransactionById($id);
        return response()->json($transaction);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|string',
            'amount' => 'sometimes|numeric',
            'description' => 'sometimes|string',
            'status' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $transaction = $this->transactionRepo->updateTransaction($id, $request->all());
        return response()->json($transaction);
    }

    public function destroy($id)
    {
        $this->transactionRepo->deleteTransaction($id);
        return response()->json(['message' => 'Transaction deleted']);
    }

    public function getByType($type)
    {
        $transactions = $this->transactionRepo->getTransactionsByType($type);
        return response()->json($transactions);
    }

    public function getByUser($userId)
    {
        $transactions = $this->transactionRepo->getTransactionsByUser($userId);
        return response()->json($transactions);
    }
}