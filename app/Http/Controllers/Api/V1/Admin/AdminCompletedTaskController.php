<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Admin\AdminCompletedTaskRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminCompletedTaskController extends Controller
{
    protected $completedTaskRepo;

    public function __construct(AdminCompletedTaskRepository $completedTaskRepo)
    {
        $this->middleware(['auth:sanctum', 'role:superadministrator']);
        $this->completedTaskRepo = $completedTaskRepo;
    }

    public function index()
    {
        $completedTasks = $this->completedTaskRepo->getAllCompletedTasks();
        return response()->json($completedTasks);
    }

    public function show($id)
    {
        $completedTask = $this->completedTaskRepo->getCompletedTaskById($id);
        return response()->json($completedTask);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string',
            'social_media_url' => 'sometimes|string',
            'screenshot' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $completedTask = $this->completedTaskRepo->updateCompletedTask($id, $request->all());
        return response()->json($completedTask);
    }

    public function destroy($id)
    {
        $this->completedTaskRepo->deleteCompletedTask($id);
        return response()->json(['message' => 'Completed task deleted']);
    }

    public function approve($id)
    {
        try {
            $completedTask = $this->completedTaskRepo->approveCompletedTask($id);
            return response()->json($completedTask);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function reject($id)
    {
        try {
            $completedTask = $this->completedTaskRepo->rejectCompletedTask($id);
            return response()->json($completedTask);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getByStatus($status)
    {
        $completedTasks = $this->completedTaskRepo->getCompletedTasksByStatus($status);
        return response()->json($completedTasks);
    }

    public function getByUser($userId)
    {
        $completedTasks = $this->completedTaskRepo->getCompletedTasksByUser($userId);
        return response()->json($completedTasks);
    }

    public function getByAdvert($advertId)
    {
        $completedTasks = $this->completedTaskRepo->getCompletedTasksByAdvert($advertId);
        return response()->json($completedTasks);
    }
}