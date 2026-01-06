<?php

namespace App\Repository\Admin;

use App\Models\ResellerConversion;
use App\Repository\Admin\IAdminResellerConversionRepository;

class AdminResellerConversionRepository implements IAdminResellerConversionRepository
{
    public function getAllConversions()
    {
        return ResellerConversion::with(['reseller', 'product'])->paginate(20);
    }

    public function getConversionById($id)
    {
        return ResellerConversion::with(['reseller', 'product'])->findOrFail($id);
    }

    public function updateConversion($id, array $data)
    {
        $conversion = ResellerConversion::findOrFail($id);
        $conversion->update($data);
        return $conversion;
    }

    public function deleteConversion($id)
    {
        $conversion = ResellerConversion::findOrFail($id);
        $conversion->delete();
        return true;
    }

    public function getConversionsByReseller($resellerId)
    {
        return ResellerConversion::where('reseller_id', $resellerId)->with(['reseller', 'product'])->paginate(20);
    }

    public function getConversionsByProduct($productId)
    {
        return ResellerConversion::where('product_id', $productId)->with(['reseller', 'product'])->paginate(20);
    }
}