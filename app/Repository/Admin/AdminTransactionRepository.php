<?php

namespace App\Repository\Admin;

use App\Models\Transaction;
use App\Repository\Admin\IAdminTransactionRepository;

class AdminTransactionRepository implements IAdminTransactionRepository
{
    public function getAllTransactions()
    {
        return Transaction::with('user')->paginate(20);
    }

    public function getTransactionById($id)
    {
        return Transaction::with('user')->findOrFail($id);
    }

    public function updateTransaction($id, array $data)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->update($data);
        return $transaction;
    }

    public function deleteTransaction($id)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->delete();
        return true;
    }

    public function getTransactionsByType($type)
    {
        return Transaction::where('type', $type)->with('user')->paginate(20);
    }

    public function getTransactionsByUser($userId)
    {
        return Transaction::where('user_id', $userId)->with('user')->paginate(20);
    }
}