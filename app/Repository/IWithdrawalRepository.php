<?php
namespace App\Repository;
use App\Models\Withdrawal;
use App\Repository\IWithdrawalRepository;

interface IWithdrawalRepository
{
    public function create(array $data): Withdrawal;
}