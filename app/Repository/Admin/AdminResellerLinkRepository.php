<?php

namespace App\Repository\Admin;

use App\Models\ResellerLink;
use App\Repository\Admin\IAdminResellerLinkRepository;

class AdminResellerLinkRepository implements IAdminResellerLinkRepository
{
    public function getAllLinks()
    {
        return ResellerLink::with(['user', 'product'])->paginate(20);
    }

    public function getLinkById($id)
    {
        return ResellerLink::with(['user', 'product'])->findOrFail($id);
    }

    public function updateLink($id, array $data)
    {
        $link = ResellerLink::findOrFail($id);
        $link->update($data);
        return $link;
    }

    public function deleteLink($id)
    {
        $link = ResellerLink::findOrFail($id);
        $link->delete();
        return true;
    }

    public function createLink(array $data)
    {
        if (!isset($data['unique_link'])) {
            $data['unique_link'] = (new ResellerLink())->generateUniqueLink();
        }
        return ResellerLink::create($data);
    }

    public function getLinksByUser($userId)
    {
        return ResellerLink::where('user_id', $userId)->with(['user', 'product'])->paginate(20);
    }

    public function getLinksByProduct($productId)
    {
        return ResellerLink::where('product_id', $productId)->with(['user', 'product'])->paginate(20);
    }
}