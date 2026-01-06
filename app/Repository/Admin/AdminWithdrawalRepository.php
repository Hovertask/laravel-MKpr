<?php

namespace App\Repository\Admin;

use App\Models\Withdrawal;
use App\Repository\Admin\IAdminWithdrawalRepository;

class AdminWithdrawalRepository implements IAdminWithdrawalRepository
{
    public function getAllWithdrawals()
    {
        return Withdrawal::with('user')->paginate(20);
    }

    public function getWithdrawalById($id)
    {
        return Withdrawal::with('user')->findOrFail($id);
    }

    public function updateWithdrawal($id, array $data)
    {
        $withdrawal = Withdrawal::findOrFail($id);
        $withdrawal->update($data);
        return $withdrawal;
    }

    public function deleteWithdrawal($id)
    {
        $withdrawal = Withdrawal::findOrFail($id);
        $withdrawal->delete();
        return true;
    }

    public function getWithdrawalsByStatus($status)
    {
        return Withdrawal::where('status', $status)->with('user')->paginate(20);
    }

    public function getWithdrawalsByUser($userId)
    {
        return Withdrawal::where('user_id', $userId)->with('user')->paginate(20);
    }
}