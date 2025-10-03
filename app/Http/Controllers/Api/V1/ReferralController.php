<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\ReferralRepository;

class ReferralController extends Controller
{
    protected ReferralRepository $repo;

    public function __construct(ReferralRepository $repo)
    {
        $this->repo = $repo;
        $this->middleware('auth:sanctum'); // ensure auth
    }

    /**
     * GET /api/v1/referrals
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $data = $this->repo->getUserReferrals($user->id);

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }
}
