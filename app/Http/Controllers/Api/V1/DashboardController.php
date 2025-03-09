<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\DashboardRepository;

class DashboardController extends Controller
{
    protected $DashboardRepository;

    public function __construct(DashboardRepository $DashboardRepository)
    {
        $this->DashboardRepository = $DashboardRepository;
    }

    public function dashboard()
    {
        return $this->DashboardRepository->getDashboardData();
    }
}
