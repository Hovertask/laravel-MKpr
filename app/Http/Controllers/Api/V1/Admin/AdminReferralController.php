<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Admin\AdminReferralRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminReferralController extends Controller
{
    protected $referralRepo;

    public function __construct(AdminReferralRepository $referralRepo)
    {
        $this->middleware(['auth:sanctum', 'role:superadministrator']);
        $this->referralRepo = $referralRepo;
    }

    public function index()
    {
        $referrals = $this->referralRepo->getAllReferrals();
        return response()->json($referrals);
    }

    public function show($id)
    {
        $referral = $this->referralRepo->getReferralById($id);
        return response()->json($referral);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reward_status' => 'sometimes|string',
            'amount' => 'sometimes|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $referral = $this->referralRepo->updateReferral($id, $request->all());
        return response()->json($referral);
    }

    public function destroy($id)
    {
        $this->referralRepo->deleteReferral($id);
        return response()->json(['message' => 'Referral deleted']);
    }

    public function markAsPaid($id)
    {
        $referral = $this->referralRepo->markAsPaid($id);
        return response()->json($referral);
    }

    public function getByStatus($status)
    {
        $referrals = $this->referralRepo->getReferralsByStatus($status);
        return response()->json($referrals);
    }

    public function getByReferrer($referrerId)
    {
        $referrals = $this->referralRepo->getReferralsByReferrer($referrerId);
        return response()->json($referrals);
    }

    public function getByReferee($refereeId)
    {
        $referrals = $this->referralRepo->getReferralsByReferee($refereeId);
        return response()->json($referrals);
    }
}