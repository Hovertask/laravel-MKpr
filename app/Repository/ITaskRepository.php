<?php

namespace App\Repository;

use App\Models\Task;

interface ITaskRepository
{
    public function create(array $data): Task;
    public function update($id, array $data);
    public function delete($id);
    public function showAll();
    public function show($id);
    public function submitTask(array $data, $id);
    public function approveTask($id);
    public function approveCompletedTask($id);
}