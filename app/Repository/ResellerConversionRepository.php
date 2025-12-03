<?php

namespace App\Repository;

use App\Models\ResellerConversion;

class ResellerConversionRepository
{
    /**
     * Check if visitor already converted for this reseller.
     */
    public function exists(string $resellerCode, string $visitorCookie): bool
    {
        return ResellerConversion::where('reseller_code', $resellerCode)
            ->where('visitor_cookie', $visitorCookie)
            ->exists();
    }

    /**
     * Store a new conversion.
     */
    public function create(array $data): ResellerConversion
    {
        return ResellerConversion::create($data);
    }

    //fetch covertions for a reseller

    public function getConversionsForReseller(int $resellerId)
    {
        return ResellerConversion::query()
            ->with(['product:id,name,price']) // Only required fields
            ->where('reseller_id', $resellerId)
            ->orderBy('id', 'DESC')
            ->paginate(20); // Performance-friendly pagination
    }
}
