<?php

namespace App\Repository;
use DB;
use App\Models\Task;
use App\Models\Wallet;
use App\Models\FundsRecord;
use Illuminate\Http\Request;
use App\Models\CompletedTask;
use App\Services\FileUploadService;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
//use Illuminate\Support\Facades\DB;

class TaskRepository implements ITaskRepository
{
    protected $fileUploadService;

    // Inject FileUploadService in the constructor
    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function create(array $data): Task
    {
        $task = Task::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'location' => $data['location']  ?? null,
            'gender' => $data['gender']  ?? null,
            'religion' => $data['religion']  ?? null,
            'no_of_participants' => $data['no_of_participants']  ?? null,
            'social_media_url' => $data['social_media_url']  ?? null,
            'type_of_comment' => $data['type_of_comment']  ?? null,
            'payment_per_task' => $data['payment_per_task']  ?? null,
            'task_duration' => $data['task_duration']  ?? null,
            'task_count_total' => $data['task_count_total'],
            'task_count_remaining' =>$data['task_count_remaining'],
            'task_amount' => $data['task_amount'],
            'task_type' => $data['task_type'],
            'user_id' => auth()->id(),
            'status' => $data['status'],
            'priority' => $data['priority'],
            'category' => $data['category'],
            'platforms' => $data['platforms'],
            'start_date' => $data['start_date'] ?? null,
            'due_date' => $data['due_date'] ?? null,
        ]);

        //dd($task);

        return $task;
    }

    public function update($id, array $data)
    {
        $task = Task::find($id);
        
        if ($task) {
            $task->update($data);
        }

        return $task;
    }


    //track all available tasks for users to pick from
    public function showAll() 
    {
        $user = auth()->user();
        $task = Task::all();
        //rectify thid when i'm sure of user data
        // $task = Task::where('location', $user->state)
        // ->where('status', 'active')
        // ->where('religion', '>', $user->religion)
        // ->where('gender', $user->gender)
        // ->orWhere('religion', null)
        // ->orWhere('location', null)
        // ->where('task_count_remaining', '>', 0)
        // ->orderBy('created_at', 'desc')
        // ->get();
        return $task;
    }


    //track task by id for users to see details
    public function show($id) {
        $task = Task::find($id);

        return $task;
    }


    //submit task done by user

public function submitTask(Request $request, $id)
{
    DB::beginTransaction();

    try {
        $task = Task::findOrFail($id);
        $userId = auth()->id();

        // 🧩 Check if the user already submitted this advert
        $existingSubmission = CompletedTask::where('user_id', $userId)
            ->where('task_id', $task->id)
            ->exists();

        if ($existingSubmission) {
            // ❌ Stop here, do not create new record
            return response()->json([
                'status' => false,
                'type' => 'duplicate', // 👈 helpful for frontend modal
                'message' => 'You have already submitted proof for this task.',
            ], 400);
        }

        // 🧩 Check if task is still available
        if ($task->task_count_remaining <= 0) {
            return response()->json([
                'status' => false,
                'type' => 'unavailable',
                'message' => 'This task is no longer available.',
            ], 404);
        }

        // 🧩 Handle upload (image or video)
        $screenshotPath = null;
        if ($request->hasFile('screenshot') && $request->file('screenshot')->isValid()) {
            $file = $request->file('screenshot');
            $mimeType = $file->getMimeType();

            // Detect type automatically
            $resourceType = str_starts_with($mimeType, 'video') ? 'video' : 'image';

            $upload = Cloudinary::uploadFile(
                $file->getRealPath(),
                [
                    'folder' => 'task',
                    'resource_type' => $resourceType,
                ]
            );

            $screenshotPath = $upload->getSecurePath();
        }

        // 🧩 Decrement available slots
        $task->decrement('task_count_remaining');

        // 🧩 Save completed task record
        CompletedTask::create([
            'user_id' => $userId,
            'task_id' => $task->id,
            'social_media_url' => $request->input('social_media_url'),
            'screenshot' => $screenshotPath,
            'payment_per_task' => $task->payment_per_task,
            'title' => $task->title,
        ]);

        // 🧩 Record pending funds
        FundsRecord::create([
            'user_id' => $userId,
            'pending' => $task->payment_per_task,
            'type' => 'task',
        ]);

        DB::commit();

        return response()->json([
            'status' => true,
            'type' => 'success',
            'message' => 'Task submitted successfully and is pending review.',
        ], 200);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'status' => false,
            'type' => 'error',
            'message' => 'Something went wrong: ' . $e->getMessage(),
        ], 500);
    }
}

    

    /**
     * Approve a task, given its id.
     * 
     * This method first updates the task's status to approved and then increments the user's balance
     * by the amount of the task.
     * 
     * @param  int  $id
     * @return \App\Models\Task
     */
    public function approveCompletedTask($id) {
        //$userId = auth()->id();
    
        try {
            DB::beginTransaction();
            $task = CompletedTask::where('id', $id)->where('status', 'pending')->first();
            if (!$task) {    
                DB::rollBack();
                return null;
            }

            $taskOwnerId = $task->user_id;
            $task->update(['status' => 'approved']);
    
            // Fund the user's wallet
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $taskOwnerId],
                ['balance' => 0]
            );
    
            $wallet->increment('balance', $task->task->task_amount);

            // Update user's main balance
            $user = User::find($taskOwnerId);
            if ($user) {
                $user->increment('balance', $task->task->task_amount);
            }

            FundsRecord::updateOrCreate(
                ['user_id' => $taskOwnerId,
                'pending' => $task->task->task_amount, 'type' => 'task'],
                ['pending' => 0,
                    'earned' => $task->task->task_amount,
    
                ],
            );
    
            DB::commit();
            return $task;
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function approveTask($id) {
        $task = Task::where('id', $id)
        ->where('status', 'pending')->first();

        //dd($task);\

        if (!$task) {
            return null;
        }

        $task->update(['status' => 'approved']);
        return $task;
    }


public function getTasksByType($type = null)
{
    $query = CompletedTask::with('user')
    ->where('user_id', auth()->id());


    switch ($type) {
        case 'pending':
            return $query->where('status', 'pending')->get();

        case 'completed':
        case 'approved':
            return $query->where('status', 'approved')->get();

        case 'rejected':
            return $query->where('status', 'rejected')->get();

        case 'history':
            return $query->get();

        default:
            return collect(); // empty collection for invalid types
    }
}

public function CompletedTaskStats()
{
    $tasks = CompletedTask::select('status', 'payment_per_task')
        ->get()
        ->groupBy('status');

    // Get counts
    $pendingCount  = $tasks->get('pending')?->count() ?? 0;
    $approvedCount = $tasks->get('approved')?->count() ?? 0;
    $rejectedCount = $tasks->get('rejected')?->count() ?? 0;

    // Calculate total earnings only for approved tasks
    $totalEarnings = $tasks->get('approved') 
        ? $tasks->get('approved')->sum('payment_per_task')
        : 0;

    // Calculate total number of all tasks
    $totalTasks = $tasks->flatten()->count();

    return [
        'pending'        => $pendingCount,
        'approved'       => $approvedCount,
        'rejected'       => $rejectedCount,
        'total_tasks'    => $totalTasks,
        'total_earnings' => $totalEarnings,
    ];
}

    public function delete($id) {
        $task = Task::find($id);
        $task->delete();
        return $task;
    }

}