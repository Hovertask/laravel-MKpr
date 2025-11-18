<?php

namespace App\Services;

use App\Repository\TransactionRepository;
use Illuminate\Support\Collection;

class TransactionService
{
    public function __construct(
        protected TransactionRepository $transactionRepository
    ) {}

    public function getUserTransactions(int $userId): Collection
    {
        return $this->transactionRepository->getTransactionsForUser($userId);
    }

    public function getUserTransaction(int $userId, int $transactionId)
    {
        return $this->transactionRepository->getUserTransaction($userId, $transactionId);
    }
}

