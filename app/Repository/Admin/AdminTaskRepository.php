<?php

namespace App\Repository\Admin;

use App\Models\Task;
use App\Repository\Admin\IAdminTaskRepository;

class AdminTaskRepository implements IAdminTaskRepository
{
    public function getAllTasks()
    {
        return Task::with(['user', 'advert', 'completedTasks'])->paginate(20);
    }

    public function getTaskById($id)
    {
        return Task::with(['user', 'advert', 'completedTasks.user'])->findOrFail($id);
    }

    public function updateTask($id, array $data)
    {
        $task = Task::findOrFail($id);
        $task->update($data);
        return $task;
    }

    public function deleteTask($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();
        return true;
    }

    public function getTasksByStatus($status)
    {
        return Task::where('status', $status)->with(['user', 'advert'])->paginate(20);
    }

    public function getTasksByUser($userId)
    {
        return Task::where('user_id', $userId)->with(['user', 'advert'])->paginate(20);
    }

    public function getTasksByType($type)
    {
        return Task::where('task_type', $type)->with(['user', 'advert'])->paginate(20);
    }
}