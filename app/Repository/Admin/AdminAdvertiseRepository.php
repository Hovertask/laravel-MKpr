<?php

namespace App\Repository\Admin;

use App\Models\Advertise;
use App\Repository\Admin\IAdminAdvertiseRepository;

class AdminAdvertiseRepository implements IAdminAdvertiseRepository
{
    public function getAllAdvertises()
    {
        return Advertise::with(['user', 'advertiseImages', 'completedTasks'])->paginate(20);
    }

    public function getAdvertiseById($id)
    {
        return Advertise::with(['user', 'advertiseImages', 'completedTasks.user'])->findOrFail($id);
    }

    public function updateAdvertise($id, array $data)
    {
        $advertise = Advertise::findOrFail($id);
        $advertise->update($data);
        return $advertise;
    }

    public function deleteAdvertise($id)
    {
        $advertise = Advertise::findOrFail($id);
        $advertise->delete();
        return true;
    }

    public function approveAdvertise($id)
    {
        $advertise = Advertise::findOrFail($id);
        $advertise->update(['admin_approval_status' => 'approved', 'status' => 'success']);
        return $advertise;
    }

    public function rejectAdvertise($id)
    {
        $advertise = Advertise::findOrFail($id);
        $advertise->update(['admin_approval_status' => 'rejected', 'status' => 'rejected']);
        return $advertise;
    }

    public function getAdvertisesByStatus($status)
    {
        return Advertise::where('status', $status)->with(['user', 'advertiseImages'])->paginate(20);
    }

    public function getAdvertisesByUser($userId)
    {
        return Advertise::where('user_id', $userId)->with(['user', 'advertiseImages'])->paginate(20);
    }
}