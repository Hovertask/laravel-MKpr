<?php

namespace App\Repository;

interface IWalletRepository
{
    public function initializePayment(int $userId, float $amount);
    public function verifyPayment(string $reference);
    public function getBalance(int $userId);

}
