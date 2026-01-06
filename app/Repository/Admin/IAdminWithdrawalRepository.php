<?php

namespace App\Repository\Admin;

interface IAdminWithdrawalRepository
{
    public function getAllWithdrawals();
    public function getWithdrawalById($id);
    public function updateWithdrawal($id, array $data);
    public function deleteWithdrawal($id);
    public function getWithdrawalsByStatus($status);
    public function getWithdrawalsByUser($userId);
}