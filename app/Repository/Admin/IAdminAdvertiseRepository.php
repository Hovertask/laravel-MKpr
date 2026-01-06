<?php

namespace App\Repository\Admin;

interface IAdminAdvertiseRepository
{
    public function getAllAdvertises();
    public function getAdvertiseById($id);
    public function updateAdvertise($id, array $data);
    public function deleteAdvertise($id);
    public function approveAdvertise($id);
    public function rejectAdvertise($id);
    public function getAdvertisesByStatus($status);
    public function getAdvertisesByUser($userId);
}