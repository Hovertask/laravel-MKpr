<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Repository\IAdvertiseRepository;
use App\Repository\TaskRepository;

class AdvertiseController extends Controller
{
    protected IAdvertiseRepository $advertiseRepository;
    protected TaskRepository $taskRepository;

    /**
     * AdvertiseController constructor.
     *
     * @param IAdvertiseRepository $advertiseRepository
     * @param TaskRepository $taskRepository
     */
    public function __construct(IAdvertiseRepository $advertiseRepository, TaskRepository $taskRepository)
    {
        $this->advertiseRepository = $advertiseRepository;
        $this->taskRepository = $taskRepository;
    }

    /**
     * Create a new advertisement or engagement task.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $type = $request->input('type'); // 'engagement' OR 'advert'

        // ✅ Validation rules
        $rules = [
            'religion'       => 'required|string',
            'platform'       => 'required|string',
            'audience'       => 'required|string',
            'region'         => 'required|string',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'budget'         => 'required|numeric|min:1',
            'description'    => 'required|string',
            'type'           => 'required|in:engagement,advert',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            if ($type === 'advert') {
                $advert = $this->advertiseRepository->createAdvert($request->all());

                return response()->json([
                    'status'  => true,
                    'message' => 'Advert created successfully',
                    'data'    => $advert,
                ], 201);
            }

            if ($type === 'engagement') {
                $task = $this->taskRepository->createEngagementTask($request->all());

                return response()->json([
                    'status'  => true,
                    'message' => 'Engagement task created successfully',
                    'data'    => $task,
                ], 201);
            }

            return response()->json([
                'status'  => false,
                'message' => 'Invalid request type',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'An error occurred while processing your request',
                'error'   => $e->getMessage(),
            ], 500);
        }
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
                'stats' => [
                    'total_participants' => $showadvert->userTasks->count(),
                    'accepted' => $showadvert->userTasks->where('status', 'accepted')->count(),
                    'rejected' => $showadvert->userTasks->where('status', 'rejected')->count(),
                ],
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

        if (!$adminApproval) {
            return response()->json([
                'status' => false,
                'message' => 'Ads not found'
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
            'message' => 'Ads updated successfully',
            'data' => $createAds,
        ]);
    }

    public function destroy($id)
    {
        $delete = $this->AdvertiseRepository->destroy($id);

        return response()->json([
            'status' => true,
            'message' => 'Ads deleted successfully',
            'data' => $delete,
        ]);
    }

    public function payAdvertFee(Request $request)
    {
        $user = $request->user();

        if ($user->has_paid_advert_fee) {
            return response()->json([
                'message' => 'You have already paid the advert setup fee.'
            ], 400);
        }

        $wallet = \App\Models\Wallet::where('user_id', $user->id)->firstOrFail();

        if ($wallet->balance < 500) {
            return response()->json([
                'message' => 'Insufficient balance. Please fund your wallet.'
            ], 400);
        }

        $wallet->balance -= 500;
        $wallet->save();

        $user->balance = $wallet->balance;
        $user->has_paid_advert_fee = true;
        $user->save();

        \App\Models\Transaction::create([
            'user_id' => $user->id,
            'amount' => -500,
            'type' => 'debit',
            'status' => 'successful',
            'description' => 'One-time advert setup fee',
        ]);

        return response()->json([
            'message' => 'Advert setup fee paid successfully.',
            'balance' => $wallet->balance,
            'has_paid_advert_fee' => true,
        ], 200);
    }
}
