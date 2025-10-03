<?php

namespace App\Repository;

use App\Models\Referral;

class ReferralRepository
{
    /**
     * Return referral summary for a given referrer (user) id
     *
     * @param int $userId
     * @return array
     */
    public function getUserReferrals(int $userId): array
    {
        // eager load referee minimal columns
        $referrals = Referral::with([
            'referee:id,fname,lname,email,avatar,created_at'
        ])->where('referrer_id', $userId)
          ->orderByDesc('created_at')
          ->get();

        $pending = Referral::where('referrer_id', $userId)
            ->where('reward_status', 'pending')
            ->sum('amount');

        $completed = Referral::where('referrer_id', $userId)
            ->where('reward_status', 'paid')
            ->sum('amount');

        $count = $referrals->count();

        // map referrals to simple array for JSON
        $refList = $referrals->map(function ($r) {
            return [
                'id' => $r->id,
                'amount' => (float) $r->amount,
                'status' => $r->reward_status,
                'created_at' => $r->created_at ? $r->created_at->toDateTimeString() : null,
                'referee' => $r->referee ? [
                    'id' => $r->referee->id,
                    'fname' => $r->referee->fname,
                    'lname' => $r->referee->lname,
                    'email' => $r->referee->email,
                    'avatar' => $r->referee->avatar,
                    'created_at' => $r->referee->created_at ? $r->referee->created_at->toDateTimeString() : null,
                ] : null,
            ];
        });

        return [
            'count' => $count,
            'pending' => (float) $pending,
            'completed' => (float) $completed,
            'total' => (float) ($pending + $completed),
            'referrals' => $refList,
        ];
    }
}
