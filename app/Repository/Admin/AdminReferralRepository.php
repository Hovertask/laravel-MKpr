<?php

namespace App\Repository\Admin;

use App\Models\Referral;
use App\Repository\Admin\IAdminReferralRepository;

class AdminReferralRepository implements IAdminReferralRepository
{
    public function getAllReferrals()
    {
        return Referral::with(['referrer', 'referee'])->paginate(20);
    }

    public function getReferralById($id)
    {
        return Referral::with(['referrer', 'referee'])->findOrFail($id);
    }

    public function updateReferral($id, array $data)
    {
        $referral = Referral::findOrFail($id);
        $referral->update($data);
        return $referral;
    }

    public function deleteReferral($id)
    {
        $referral = Referral::findOrFail($id);
        $referral->delete();
        return true;
    }

    public function getReferralsByStatus($status)
    {
        return Referral::where('reward_status', $status)->with(['referrer', 'referee'])->paginate(20);
    }

    public function getReferralsByReferrer($referrerId)
    {
        return Referral::where('referrer_id', $referrerId)->with(['referrer', 'referee'])->paginate(20);
    }

    public function getReferralsByReferee($refereeId)
    {
        return Referral::where('referee_id', $refereeId)->with(['referrer', 'referee'])->paginate(20);
    }

    public function markAsPaid($id)
    {
        $referral = Referral::findOrFail($id);
        $referral->update(['reward_status' => 'paid']);
        return $referral;
    }
}