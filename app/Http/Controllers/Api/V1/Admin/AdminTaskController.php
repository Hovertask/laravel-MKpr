<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Admin\AdminTaskRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminTaskController extends Controller
{
    protected $taskRepo;

    public function __construct(AdminTaskRepository $taskRepo)
    {
        $this->middleware(['auth:sanctum', 'role:superadministrator']);
        $this->taskRepo = $taskRepo;
    }

    public function index()
    {
        $tasks = $this->taskRepo->getAllTasks();
        return response()->json($tasks);
    }

    public function show($id)
    {
        $task = $this->taskRepo->getTaskById($id);
        return response()->json($task);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string',
            'description' => 'sometimes|string',
            'platforms' => 'sometimes|array',
            'task_amount' => 'sometimes|numeric',
            'task_type' => 'sometimes|string',
            'task_count_total' => 'sometimes|integer',
            'task_count_remaining' => 'sometimes|integer',
            'priority' => 'sometimes|string',
            'start_date' => 'sometimes|date',
            'due_date' => 'sometimes|date',
            'gender' => 'sometimes|string',
            'location' => 'sometimes|string',
            'no_of_participants' => 'sometimes|integer',
            'payment_per_task' => 'sometimes|numeric',
            'religion' => 'sometimes|string',
            'social_media_url' => 'sometimes|string',
            'type_of_comment' => 'sometimes|string',
            'status' => 'sometimes|string',
            'completed' => 'sometimes|boolean',
            'category' => 'sometimes|string',
            'payment_method' => 'sometimes|string',
            'payment_gateway' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task = $this->taskRepo->updateTask($id, $request->all());
        return response()->json($task);
    }

    public function destroy($id)
    {
        $this->taskRepo->deleteTask($id);
        return response()->json(['message' => 'Task deleted']);
    }

    public function getByStatus($status)
    {
        $tasks = $this->taskRepo->getTasksByStatus($status);
        return response()->json($tasks);
    }

    public function getByUser($userId)
    {
        $tasks = $this->taskRepo->getTasksByUser($userId);
        return response()->json($tasks);
    }

    public function getByType($type)
    {
        $tasks = $this->taskRepo->getTasksByType($type);
        return response()->json($tasks);
    }
}