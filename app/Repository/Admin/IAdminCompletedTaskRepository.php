<?php

namespace App\Repository\Admin;

interface IAdminCompletedTaskRepository
{
    public function getAllCompletedTasks();
    public function getCompletedTaskById($id);
    public function updateCompletedTask($id, array $data);
    public function deleteCompletedTask($id);
    public function approveCompletedTask($id);
    public function rejectCompletedTask($id);
    public function getCompletedTasksByStatus($status);
    public function getCompletedTasksByUser($userId);
    public function getCompletedTasksByAdvert($advertId);
}