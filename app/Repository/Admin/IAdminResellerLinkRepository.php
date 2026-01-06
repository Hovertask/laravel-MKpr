<?php

namespace App\Repository\Admin;

interface IAdminResellerLinkRepository
{
    public function getAllLinks();
    public function getLinkById($id);
    public function updateLink($id, array $data);
    public function deleteLink($id);
    public function createLink(array $data);
    public function getLinksByUser($userId);
    public function getLinksByProduct($productId);
}