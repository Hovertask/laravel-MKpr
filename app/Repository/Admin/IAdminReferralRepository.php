<?php

namespace App\Repository\Admin;

interface IAdminReferralRepository
{
    public function getAllReferrals();
    public function getReferralById($id);
    public function updateReferral($id, array $data);
    public function deleteReferral($id);
    public function getReferralsByStatus($status);
    public function getReferralsByReferrer($referrerId);
    public function getReferralsByReferee($refereeId);
    public function markAsPaid($id);
}