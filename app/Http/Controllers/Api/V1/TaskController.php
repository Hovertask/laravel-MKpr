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
            'priority' => 'required|string|in:high,low,medium',
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
            'category' => 'required|string|in:social_media,onlinvideo_markettinge,micro_influence,promotion,telegram',
            'file_path' => 'nullable|array',
            'file_path.*' => 'file|mimes:jpeg,png,jpg|max:10240',
        
            'video_path' => 'nullable|array',
            'video_path.*' => 'file|mimes:mp4,mov,avi,gif|max:10240',
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

   

    //track all task created by auth user

    public function authUserTasks()
    {
        $authUserTasks = $this->task->authUserTasks();

        return response()->json([
            'status' => true,
            'message' => 'Ads retrieved successfully',
            'data' => $authUserTasks,
        ], 200);
    }



    //track task by id for  creator to track task perfomance
    public function showTaskPerformance($id)
{
    $showtask = $this->task->showTaskPerformance($id);

    if (!$showtask) {
        return response()->json([
            'status' => false,
            'message' => 'Task not found',
        ], 404);
    }

    return response()->json([
        'status' => true,
        'message' => 'Task retrieved successfully',
        'data' => [
            'id' => $showtask->id,
            'title' => $showtask->title,
            'description' => $showtask->description,
            'amount_paid' => $showtask->task_amount ?? 0,
            'link' => $showtask->link ?? null,
            'status' => $showtask->status,
            'payment_per_task' => $showtask->payment_per_task ?? null,
            'no_of_status_post' => $showtask->no_of_status_post ?? null,
            'created_at' => $showtask->created_at->toDateTimeString(),


            // computed stats
            'stats' => [
                'total_participants' => $showtask->completedTasks->count(),
                'accepted' => $showtask->CompletedTasks->where('status', 'accepted')->count(),
                'rejected' => $showtask->CompletedTasks->where('status', 'rejected')->count(),
                'pending' => $showtask->CompletedTasks->where('status', 'pending')->count(),
                'total_count' => $showtask->task_count_total ?? 0,
                'remaining_count' => $showtask->task_count_remaining ?? 0,
                'completed_count' => ($showtask->task_count_total ?? 0) - ($showadvert->task_count_remaining ?? 0),
                'completion_percentage' => ($showtask->task_count_total ?? 0) > 0
                ? round((($showtask->task_count_total - $showtask->task_count_remaining) / $showtask->task_count_total) * 100, 2)
                : 0,
                'BudgetSpent' => ($showtask->estimated_cost ?? 0) - ($showtask->payment_per_task ?? 0),
            ],

            // mapped participants
            'participants' => $showtask->completedTasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'name' => $task->user->fname ?? 'Unknown',
                    'handle' => '@' . ($task->user->username ?? 'unknown'),
                    'proof_link' => $task->social_media_url,
                    'screenshot_path' => $task->screenshot,
                    'status' => $task->status,
                    'submitted_at' => $task->created_at->toDateTimeString(),
                ];
            }),
        ],
    ], 200);
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


    // track all available tasks for users to pick from
    public function showAll()
    {
        $tasks = $this->task->showAll();

        if (!$tasks) {
            return response()->json([
                'status' => false,
                'message' => 'No Available Tasks found at the moment',
            ], 404);
        }

        foreach($tasks as $task) {
            // Format created_at
            $task->created_at = $task->created_at->diffForHumans();
            
            // Calculate completion percentage
            $total_task = $task->task_count_total;
            $task_completed = $total_task - $task->task_count_remaining;
            
            $completionPercentage = ($total_task > 0) 
                ? round(($task_completed / $total_task) * 100, 2)
                : 0;
            
            // Add the percentage to the task object
            $task->completion_percentage = $completionPercentage;

            $task->completed = ($task->completed == 1) ? 'Completed' : 'Available';

            //check if task is new or not
            $createdAt = $task->created_at;
            $now = now();
            $hoursDifference = $createdAt->diffInHours($now);
            
            $newStatus = ($hoursDifference < 12) ? 'New Task' : '';
                
            $task->posted_status = $newStatus;
        }

        return response()->json([
            'status' => true,
            'message' => 'Task retrieved successfully',
            'data' => $tasks,
        ], 200);
    }

    // track task by id for earners to see details to complete the task
    public function show($id)
    {
        $task = $this->task->show($id);

        if (!$task) {
            return response()->json([
                'status' => false,
                'message' => 'Task not found',
            ], 404);
        }

            $createdAt = $task->created_at;
            $now = now();
            $hoursDifference = $createdAt->diffInHours($now);
            
            $newStatus = ($hoursDifference < 12) ? 'new' : '';
                
            $total_task = $task->task_count_total;
            $task_completed = $total_task - $task->task_count_remaining;
            
            $completionPercentage = ($total_task > 0) 
                ? round(($task_completed / $total_task) * 100, 2)
                : 0;
            
            // Add the percentage to the task object
            $task->completion_percentage = $completionPercentage;
            $task->completed = ($task->completed == 1) ? 'Completed' : 'Available';

            $task->posted_status = $newStatus;

        return response()->json([
            'status' => true,
            'message' => 'Task retrieved successfully',
            'data' => $task,
        ], 200);
    }

    

    public function submitTask(Request $request, $id)
{
    $validate = Validator::make($request->all(), [
        'screenshot' => 'required|mimes:jpg,png,jpeg,mp4,mov,avi|max:10240',
    ]);

    if ($validate->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation failed',
            'errors' => $validate->errors(),
        ], 422);
    }

    return $this->task->submitTask($request, $id);
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

    

    public function deleteTask($id) {
        $task = $this->task->delete($id);
        return response()->json([
            'status' => true,
            'message' => 'Task deleted successfully',
            'data' => $task,
        ], 200);
    }

     // Fetch tasks based on type with summary stats for user who completed task

public function getTasks(Request $request)
{
    $type = $request->query('type'); // pending, accepted, rejected, history
    $tasks = $this->task->getTasksByType($type);

    if ($tasks->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => match ($type) {
                'pending' => 'No pending tasks found at the moment',
                'accepted' => 'No completed tasks found at the moment',
                'rejected' => 'No rejected tasks found at the moment',
                'history' => 'No task history found at the moment',
                default => 'Invalid task type provided. Use pending, completed, rejected, or history.',
            },
        ], $type ? 200 : 400);
    }

    return response()->json([
        'status' => true,
        'message' => ucfirst($type) . ' tasks retrieved successfully',
        'data' => $tasks,
        'stats' => $this->task->CompletedTaskStats(), // 🔹 Include summary stats
    ]);
}


/**
 * Approve an advert submission by its ID.
 *
 * This method updates the advert submission status to 'approved' or 'rejected',
 * credits the user's wallet and user account balance with the advert payment,
 * and updates the corresponding funds record if accepted.
 *
 * @param  int  $id The ID of the advert submission to update.
 * @param  string  $status The new status for the submission ('accepted' or '
 * @return \App\Models\CompletedTask|\Illuminate\Http\JsonResponse|null
 */


public function updateParticipantStatus(Request $request, $id)
{
    $validated = $request->validate([
        'status' => 'required|in:accepted,rejected',
    ]);

    $status = $validated['status'];

    $result = $this->task->updateParticipantStatus($id, $status);

    if (!$result['success']) {
        return response()->json([
            'status' => false,
            'message' => $result['message'],
        ], $result['code']);
    }

    return response()->json([
        'status' => true,
        'message' => $result['message'],
        'data' => $result['data'],
    ]);
}


}
