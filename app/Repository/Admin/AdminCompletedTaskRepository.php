<?php

namespace App\Repository\Admin;

use App\Models\CompletedTask;
use App\Models\Wallet;
use App\Models\User;
use App\Models\FundsRecord;
use DB;
use App\Repository\Admin\IAdminCompletedTaskRepository;

class AdminCompletedTaskRepository implements IAdminCompletedTaskRepository
{
    public function getAllCompletedTasks()
    {
        return CompletedTask::with(['user', 'task', 'advert'])->paginate(20);
    }

    public function getCompletedTaskById($id)
    {
        return CompletedTask::with(['user', 'task', 'advert'])->findOrFail($id);
    }

    public function updateCompletedTask($id, array $data)
    {
        $completedTask = CompletedTask::findOrFail($id);
        $completedTask->update($data);
        return $completedTask;
    }

    public function deleteCompletedTask($id)
    {
        $completedTask = CompletedTask::findOrFail($id);
        $completedTask->delete();
        return true;
    }

    public function approveCompletedTask($id)
    {
        DB::beginTransaction();
        try {
            $task = CompletedTask::findOrFail($id);
            if ($task->status !== 'pending') {
                throw new \Exception('Task already processed');
            }
            $task->update(['status' => 'accepted']);

            $amount = $task->payment_per_task;
            $userId = $task->user_id;

            // Update wallet
            $wallet = Wallet::firstOrCreate(['user_id' => $userId], ['balance' => 0]);
            $wallet->increment('balance', $amount);

            // Update user balance
            $user = User::find($userId);
            $user->increment('balance', $amount);

            // Record earning
            FundsRecord::updateOrCreate(
                ['user_id' => $userId, 'completed_task_id' => $task->id, 'type' => 'advert'],
                ['pending' => 0, 'earned' => $amount]
            );

            DB::commit();
            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function rejectCompletedTask($id)
    {
        $task = CompletedTask::findOrFail($id);
        if ($task->status !== 'pending') {
            throw new \Exception('Task already processed');
        }
        $task->update(['status' => 'rejected']);
        return $task;
    }

    public function getCompletedTasksByStatus($status)
    {
        return CompletedTask::where('status', $status)->with(['user', 'task', 'advert'])->paginate(20);
    }

    public function getCompletedTasksByUser($userId)
    {
        return CompletedTask::where('user_id', $userId)->with(['user', 'task', 'advert'])->paginate(20);
    }

    public function getCompletedTasksByAdvert($advertId)
    {
        return CompletedTask::where('advert_id', $advertId)->with(['user', 'task', 'advert'])->paginate(20);
    }
}