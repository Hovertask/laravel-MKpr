<?php

namespace App\Repository;

use App\Models\Transaction;
use Illuminate\Support\Collection;

class TransactionRepository
{
    /**
     * Fetch authenticated user's transactions
     * O(1) using user_id index.
     */
    public function getTransactionsForUser(int $userId): Collection
    {
        return Transaction::where('user_id', $userId)
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Fetch a single transaction safely
     */
    public function getUserTransaction(int $userId, int $transactionId): ?Transaction
    {
        return Transaction::where('user_id', $userId)
            ->where('id', $transactionId)
            ->first();
    }
}
