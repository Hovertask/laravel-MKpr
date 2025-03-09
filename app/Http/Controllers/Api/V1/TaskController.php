<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Repository\ITaskRepository;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    protected $task;

    public function __construct(ITaskRepository $task)
    {
        $this->task = $task;
    }

    public function createTask(Request $request) {
        $validateTask = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'title' => 'required|string',
            'description' => 'required|string',
            'status' => 'required|string',
            'priority' => 'required|string',
            'location' => 'nullable|string',
            'religion' => 'nullable|string',
            'gender' => 'nullable|string',
            'no_of_participants' => 'nullable|string',
            'task_duration' => 'nullable|string',
            'payment_per_task' => 'nullable|string',
            'type_of_comment' => 'nullable|string',
            'social_media_url' => 'nullable|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'task_amount' => 'nullable|integer',
            'task_type' => 'required|integer',
            'task_count_total' => 'nullable|integer',
            'task_count_remaining' => 'nullable|integer',
            'platforms' => 'nullable|string',
        ]);


        if ($validateTask->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validateTask->errors(),
            ], 422); 
        }

        $task = $this->task->create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Task created successfully',
            'data' => $task,
        ], 201);

    }

    public function updateTask(Request $request, $id) {
        $validateTask = Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
            'status' => 'required|string',
            'priority' => 'required|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'task_amount' => 'nullable|integer',
            'task_type' => 'required|integer',
            'task_count_total' => 'nullable|integer',
            'task_count_remaining' => 'nullable|integer',
            'platforms' => 'nullable|string',
        ]);

        if ($validateTask->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validateTask->errors(),
            ], 422);

        }

        $validatedData = $validateTask->validated();
        $task = $this->task->update($id, $validatedData);

        return response()->json([
            'status' => true,
            'message' => 'Task updated successfully',
            'data' => $task,
        ], 200);

    }

    public function showAll()
    {
        $tasks = $this->task->showAll();

        if (!$tasks) {
            return response()->json([
                'status' => false,
                'message' => 'No Available Tasks found at the moment',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Task retrieved successfully',
            'data' => $tasks,
        ], 200);
    }

    public function show($id)
    {
        $task = $this->task->show($id);

        if (!$task) {
            return response()->json([
                'status' => false,
                'message' => 'Task not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Task retrieved successfully',
            'data' => $task,
        ], 200);
    }

    public function submitTask(Request $request, $id) {
        $validate = Validator::make($request->all(), [
            'screenshot' => 'required|mimes:jpg,png,jpeg|max:2048',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validate->errors(),
            ], 422);
        }
        $task = $this->task->submitTask($request->all(), $id);        
        return response()->json([
            'status' => true,
            'message' => 'Task submitted successfully, kindly wait for approval',
            'data' => $task,
        ]);
    }

    public function approveTask(Request $request, $id) {
        $validate = Validator::make($request->all(), [
            'status' => 'required|string',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validate->errors(),
            ], 422);
        }
        $status = $validate->validated();
        $task = $this->task->approveTask($id);
            
        if (!$task) {
            return response()->json([
                'status' => false,
                'message' => 'Task not found or already approved',
            ], 404);
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Task approved successfully',
            'data' => $task,
        ], 200);
    
    }

    public function approveCompletedTask(Request $request, $id) {
        $task = $this->task->approveCompletedTask($id);
            
        if (!$task) {
            return response()->json([
                'status' => false,
                'message' => 'Task not found or already approved',
            ], 404);
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Task approved successfully',
            'data' => $task,
        ], 200);
    
    }

    public function deleteTask($id) {
        $task = $this->task->delete($id);
        return response()->json([
            'status' => true,
            'message' => 'Task deleted successfully',
            'data' => $task,
        ], 200);
    }
}
