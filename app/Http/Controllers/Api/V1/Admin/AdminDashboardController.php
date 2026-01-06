<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Admin\AdminDashboardRepository;

class AdminDashboardController extends Controller
{
    protected $dashboardRepo;

    public function __construct(AdminDashboardRepository $dashboardRepo)
    {
        $this->middleware(['auth:sanctum', 'role:superadministrator']);
        $this->dashboardRepo = $dashboardRepo;
    }

    public function index()
    {
        $stats = $this->dashboardRepo->getDashboardStats();
        return response()->json($stats);
    }
}