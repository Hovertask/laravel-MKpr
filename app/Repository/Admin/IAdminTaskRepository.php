<?php

namespace App\Repository\Admin;

interface IAdminTaskRepository
{
    public function getAllTasks();
    public function getTaskById($id);
    public function updateTask($id, array $data);
    public function deleteTask($id);
    public function getTasksByStatus($status);
    public function getTasksByUser($userId);
    public function getTasksByType($type);
}