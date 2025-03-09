<?php
namespace App\Repository;

use App\Models\Wallet;
use App\Models\Task;

interface IDashboardRepository
{
    public function getDashboardData();
}