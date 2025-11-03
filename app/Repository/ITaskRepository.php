<?php

namespace App\Repository;

use App\Models\Task;
use Illuminate\Http\Request;

interface ITaskRepository
{
    public function create(array $data): Task;
    public function update($id, array $data);
    public function delete($id);
    public function showAll();
    public function show($id);
    public function showTask($id);
    public function submitTask(Request $request, $id);
    public function approveTask($id);
    public function updateParticipantStatus($id, $status);
    public function getTasksByType($type);
    public function CompletedTaskStats();
    //public function TaskHistory();
}