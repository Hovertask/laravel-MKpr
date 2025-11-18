<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(TransactionService $transactionService){
        $this->transactionService = $transactionService;
    } 

    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $transactions = $this->transactionService
            ->getUserTransactions($userId);

        return response()->json([
            'status' => 'success',
            'data' => $transactions,
        ]);
    }

    public function show(Request $request, $id)
    {
        $userId = $request->user()->id;

        $transaction = $this->transactionService
            ->getUserTransaction($userId, $id);

        if (!$transaction) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaction not found or unauthorized.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $transaction,
        ]);
    }
}
