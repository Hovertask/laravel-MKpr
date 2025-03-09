<?php

namespace App\Repository;
use DB;
use App\Models\Task;
use App\Models\Wallet;
use App\Models\FundsRecord;
use App\Models\CompletedTask;
use App\Services\FileUploadService;
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
            'description' => $data['description'],
            'location' => $data['location']  ?? null,
            'gender' => $data['gender']  ?? null,
            'religion' => $data['religion']  ?? null,
            'no_of_participants' => $data['no_of_participants']  ?? null,
            'social_media_url' => $data['social_media_url']  ?? null,
            'type_of_comment' => $data['type_of_comment']  ?? null,
            'payment_per_task' => $data['payment_per_task']  ?? null,
            'task_duration' => $data['task_duration']  ?? null,
            'task_count_total' => $data['task_count_total'],
            'task_amount' => $data['task_amount'],
            'task_type' => $data['task_type'],
            'user_id' => auth()->id(),
            'status' => $data['status'],
            'priority' => $data['priority'],
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

    public function show($id) {
        $task = Task::find($id);

        return $task;
    }


public function submitTask(array $data, $id)
{
    DB::beginTransaction();

    try {
        $task = Task::findOrFail($id);
        $userId = auth()->id();

        //dd($task);

        $existingSubmission = CompletedTask::where('user_id', $userId)
            ->where('task_id', $task->id)
            ->exists();

            //dd($existingSubmission);
        if ($existingSubmission) {
            return response()->json([
                'status' => false,
                'message' => 'You have already submitted this task',
            ], 400);
        }

        if ($task->task_count_remaining > 0) {
            $task->decrement('task_count_remaining');
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Task is not available',
            ], 404);
        }

        $screenshotPath = null;

        // 🔹 Handle file upload
        if (isset($data['screenshot']) && $data['screenshot'] instanceof \Illuminate\Http\UploadedFile) {
            $folderName = 'tasks';
            $screenshotPath = $this->fileUploadService->upload($data['screenshot'], $folderName);
        }

        CompletedTask::create([
            'user_id' => $userId,
            'task_id' => $task->id,
            'instagram_url' => $data['instagram_url'] ?? null,
            'screenshot' => $screenshotPath,
        ]);

        FundsRecord::create([
            'user_id' => $userId,
            'pending' => $task->task_amount,
            'type' => 'task',
        ]);

       

        DB::commit();

        return $task;

    } catch (\Exception $e) {
        DB::rollBack(); // Rollback the shit, nigga probaly cheated

        return response()->json([
            'status' => false,
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
        $userId = auth()->id();
    
        try {
            DB::beginTransaction();
            $task = CompletedTask::where('id', $id)->where('status', 'pending')->first();
            if (!$task) {    
                DB::rollBack();
                return null;
            }
            $task->update(['status' => 'approved']);
    
            // Fund the user's wallet
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $userId],
                ['balance' => 0]
            );
    
            $wallet->increment('balance', $task->task->task_amount);

            FundsRecord::updateOrCreate(
                ['user_id' => $userId,
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

    public function delete($id) {
        $task = Task::find($id);
        $task->delete();
        return $task;
    }

    
    

}