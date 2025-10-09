<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\IAdvertiseRepository;
use App\Repository\AdvertiseRepository;
use App\Repository\TaskRepository;
use Illuminate\Support\Facades\Validator;

class AdvertiseController extends Controller
{
    public $AdvertiseRepository;
    protected $TaskRepository;

    public function __construct(IAdvertiseRepository $AdvertiseRepository, TaskRepository $TaskRepository)
{
    $this->AdvertiseRepository = $AdvertiseRepository;
    $this->TaskRepository = $TaskRepository;
}

    public function index()
    {
        $ads = $this->AdvertiseRepository->index();

        return response()->json([
            'status' => true,
            'Message' => 'Ads sucessfully fetched',
            'data' => $ads,
        ]);
    }


    public function create(Request $request)
    {
        $type = $request->input('type'); // engagement OR advert

        // Validation rules
        $rules = [
            'religion' => 'nullable|max:255',
            'location' => 'nullable|max:255',
            'gender' => 'nullable|max:20',
            'platforms' => 'required',
            'no_of_status_post' => 'nullable|integer',
            'file_path' => 'nullable',
            'video_path' => 'nullable',
            'description' => 'nullable|string|min:20',
            'payment_method' => 'nullable|string|max:20',
            'estimated_cost' => 'required|numeric|min:1',
        ];

        if ($type === 'engagement') {
            $rules = array_merge($rules, [
                'title' => 'nullable|string|max:255',
                'number_of_participants' => 'required|integer|min:1',
                'payment_per_task' => 'required|numeric|min:1',
                'deadline' => 'required|date|after:today',
            ]);
        } else {
            $rules = array_merge($rules, [
                'title' => 'required|string|max:255',
                'number_of_participants' => 'nullable|integer|min:1',
                'payment_per_task' => 'nullable|numeric|min:1',
                'deadline' => 'nullable|date|after:today',
            ]);
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // ✅ Create advert first
        $createAds = $this->AdvertiseRepository->create($request->all(), $request);

        // ✅ If engagement, create linked task
        $createTask = null;
        if ($type === 'engagement') {
            $taskData = [
                'advert_id' => $createAds->id, // 🔗 link to advert
                'title' => $request->input('title') ?? 'Engagement Task',
                'description' => $request->input('description'),
                'location' => $request->input('location'),
                'gender' => $request->input('gender'),
                'religion' => $request->input('religion'),
                'no_of_participants' => $request->input('number_of_participants'),
                'social_media_url' => $request->input('social_media_url'),
                'type_of_comment' => 'General',
                'payment_per_task' => $request->input('payment_per_task'),
                'task_duration' => $request->input('deadline'),
                'task_count_total' => $request->input('number_of_participants'),
                'task_amount' => $request->input('estimated_cost'),
                'task_count_remaining' => $request->input('number_of_participants'),
                'task_type' => 1,
                'status' => 'pending',
                'priority' => 'normal',
                'category' => $request->input('category'),
                'platforms' => $request->input('platforms'),
                'start_date' => now(),
                'due_date' => $request->input('deadline'),
            ];

            $createTask = $this->TaskRepository->create($taskData);
        }

        return response()->json([
            'status' => true,
            'message' => 'Ad created successfully',
            'data' => [
                'advert' => $createAds,
                'task' => $createTask,
            ],
        ]);
    }



    public function show($id)
{
    $showadvert = $this->AdvertiseRepository->show($id);

    if (!$showadvert) {
        return response()->json([
            'status' => false,
            'message' => 'Ads not found',
        ], 404);
    }

    return response()->json([
        'status' => true,
        'message' => 'Ads retrieved successfully',
        'data' => [
            'id' => $showadvert->id,
            'title' => $showadvert->title,
            'description' => $showadvert->description,
            'amount_paid' => $showadvert->amount_paid ?? 0,
            'link' => $showadvert->link ?? null,
            'admin_approval_status' => $showadvert->admin_approval_status,
            'created_at' => $showadvert->created_at->toDateTimeString(),

            // computed stats
            'stats' => [
                'total_participants' => $showadvert->userTasks->count(),
                'accepted' => $showadvert->userTasks->where('status', 'accepted')->count(),
                'rejected' => $showadvert->userTasks->where('status', 'rejected')->count(),
            ],

            // mapped participants
            'participants' => $showadvert->userTasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'name' => $task->user->name ?? 'Unknown',
                    'handle' => '@' . ($task->user->username ?? 'unknown'),
                    'proof_link' => $task->proof_link,
                    'status' => $task->status,
                    'submitted_at' => $task->created_at->toDateTimeString(),
                ];
            }),
        ],
    ], 200);
}


    public function authUserAds()
    {
        $authUserAds = $this->AdvertiseRepository->authUserAds();

        return response()->json([
            'status' => true,
            'message' => 'Ads retrieved successfully',
            'data' => $authUserAds,
        ], 200);
    }

    public function approveAds(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'admin_approval_status' => 'required|string'
        ]);
        $adminApproval = $this->AdvertiseRepository->approveAds($validator->validated(), $id);

        if(!$adminApproval)
        {
            return response()->json([
                'status' => false,
                'Mesaage' => 'Ads not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Ads retrieved successfully',
            'data' => $adminApproval,
        ], 200);
    }

    public function updateAds(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'religion' => 'sometimes|max:255',
            'location' => 'sometimes|max:255',
            'gender' => 'sometimes|max:20',
            'platforms' => 'sometimes|string',
            'no_of_status_post' => 'sometimes|integer',
            'file_path' => 'sometimes|file|mimes:jpeg,png,jpg,gif|max:2048',
            'video_path' => 'sometimes|file|mimes:mp4,mov,avi,gif|max:10240',
            'description' => 'sometimes|string|min:20',
            'payment_method' => 'sometimes|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $validateData = $validator->validated();

        $createAds = $this->AdvertiseRepository->updateAds($validateData, $request, $id);

        return response()->json([
            'status' => true,
            'Message' => 'Ads Updated successfully',
            'data' => $createAds,
        ]);
    }

    public function destroy($id)
    {
        $delete = $this->AdvertiseRepository->destroy($id);

        return response()->json([
            'status' => true,
            'Message' => 'Ads deleted successfully',
            'data' => $delete,
        ]);
    }


    public function payAdvertFee(Request $request)
{
    $user = $request->user();

    // ✅ Already paid
    if ($user->has_paid_advert_fee) {
        return response()->json([
            'message' => 'You have already paid the advert setup fee.'
        ], 400);
    }

    // ✅ Get wallet
    $wallet = \App\Models\Wallet::where('user_id', $user->id)->firstOrFail();

    // ✅ Insufficient balance
    if ($wallet->balance < 500) {
        return response()->json([
            'message' => 'Insufficient balance. Please fund your wallet.'
        ], 400);
    }

    // ✅ Deduct from wallet
    $wallet->balance -= 500;
    $wallet->save();

    // ✅ Mirror wallet balance to user table
    $user->balance = $wallet->balance;
    $user->has_paid_advert_fee = true;
    $user->save();

    // ✅ Log transaction
    \App\Models\Transaction::create([
        'user_id'       => $user->id,
        'amount'        => -500,
        'type'          => 'debit',
        'status'        => 'succesfull',
        'description'   => 'One-time advert setup fee',
    ]);

    return response()->json([
        'message'           => 'Advert setup fee paid successfully.',
        'balance'           => $wallet->balance,
        'has_paid_advert_fee' => true,
    ], 200);
}

}
