<?php
namespace App\Repository;

use App\Models\Withdrawal;
use App\Repository\IWithdrawalRepository;

class WithdrawalRepository implements IWithdrawalRepository
{
    public function create(array $data): Withdrawal
    {
        return Withdrawal::create($data);
    }

}