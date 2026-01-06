<?php

namespace App\Repository\Admin;

interface IAdminTransactionRepository
{
    public function getAllTransactions();
    public function getTransactionById($id);
    public function updateTransaction($id, array $data);
    public function deleteTransaction($id);
    public function getTransactionsByType($type);
    public function getTransactionsByUser($userId);
}