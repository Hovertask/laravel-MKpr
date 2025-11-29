<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\IAdvertiseRepository;
use App\Repository\AdvertiseRepository;
use App\Repository\TaskRepository;
use App\Models\Referral;
use App\Models\Wallet;
use App\Models\User;
use App\Models\FundsRecord;
use App\Models\Transaction;
use Illuminate\Support\Facades\Validator;
use App\Events\UserWalletUpdated;

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

    // Initialize variables
    $createAds = null;
    $createTask = null;

    // Base Validation Rules
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

    // If NOT engagement → require at least one: file OR video
    if ($type !== 'engagement') {
        $rules['file_path'] = 'required_without:video_path';
        $rules['video_path'] = 'required_without:file_path';
    }

    // Engagement-specific rules
    if ($type === 'engagement') {
        $rules = array_merge($rules, [
            'title' => 'nullable|string|max:255',
            'number_of_participants' => 'required|integer|min:1',
            'payment_per_task' => 'required|numeric|min:1',
            'deadline' => 'required|date|after:today',
        ]);
    } else {
        // Advert-specific rules
        $rules = array_merge($rules, [
            'title' => 'required|string|max:255',
            'number_of_participants' => 'nullable|integer|min:1',
            'payment_per_task' => 'nullable|numeric|min:1',
            'deadline' => 'nullable|date|after:today',
        ]);
    }

    // Validate Request
    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Create advert
    if ($type === 'advert') {
        $createAds = $this->AdvertiseRepository->create($request->all(), $request);
    }

    // Create engagement task
    if ($type === 'engagement') {
        $taskData = [
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
            'payment_method' => $request->input('payment_method'),
            'payment_gateway' => 'paystack',
            'task_type' => 1,
            'status' => 'pending',
            'priority' => 'medium',
            'category' => $request->input('category'),
            'platforms' => $request->input('platforms'),
            'start_date' => now(),
            'due_date' => $request->input('deadline'),
        ];

        $createTask = $this->TaskRepository->create($taskData);
    }

    // Build response
    $responseData = [
        'advert' => $createAds,
        'task' => $createTask,
    ];

    return response()->json([
        'status' => true,
        'message' => $type === 'advert'
            ? 'Advert created successfully'
            : 'Engagement task created successfully',
        'data' => $responseData,
    ]);
}

//track advert by id for advert creator to track perfomance
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
            'amount_paid' => $showadvert->estimated_cost ?? 0,
            'link' => $showadvert->link ?? null,
            'status' => $showadvert->status,
            'payment_per_task' => $showadvert->payment_per_task ?? null,
            'no_of_status_post' => $showadvert->no_of_status_post ?? null,
            'created_at' => $showadvert->created_at->toDateTimeString(),


            // computed stats
            'stats' => [
                'total_participants' => $showadvert->completedTasks->count(),
                'accepted' => $showadvert->CompletedTasks->where('status', 'accepted')->count(),
                'rejected' => $showadvert->CompletedTasks->where('status', 'rejected')->count(),
                'pending' => $showadvert->CompletedTasks->where('status', 'pending')->count(),
                'total_count' => $showadvert->task_count_total ?? 0,
                'remaining_count' => $showadvert->task_count_remaining ?? 0,
                'completed_count' => ($showadvert->task_count_total ?? 0) - ($showadvert->task_count_remaining ?? 0),
                'completion_percentage' => ($showadvert->task_count_total ?? 0) > 0
                ? round((($showadvert->task_count_total - $showadvert->task_count_remaining) / $showadvert->task_count_total) * 100, 2)
                : 0,
                'BudgetSpent' => ($showadvert->estimated_cost ?? 0) - ($showadvert->payment_per_task ?? 0),
            ],

            // mapped participants
            'participants' => $showadvert->completedTasks->map(function ($task) {
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
    $wallet = Wallet::where('user_id', $user->id)->firstOrFail();

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

    // Fire wallet-updated event for the paying user
    //event(new UserWalletUpdated($user->id, $user->balance));

    // ✅ Log transaction
    Transaction::create([
        'user_id'       => $user->id,
        'amount'        => 500,
        'type'          => 'debit',
        'status'        => 'successfull',
        'description'   => 'One-time advert/task/product setup fee',
        'payment_source' => 'wallet',
        'category'    => 'platform_charges',

    ]);


     // ✅ Handle referral logic
            $referral = Referral::where('referee_id', $user->id)->first();

            if ($user->is_member && $referral && $referral->reward_status == 'pending') {
                $referrer = User::find($referral->referrer_id);

                if ($referrer && $referrer->is_member) {
                    $referralAmount = 500;

                    // Update referrer's wallet
                    $referrerWallet = Wallet::firstOrCreate(
                        ['user_id' => $referrer->id],
                        ['balance' => 0]
                    );
                    $referrerWallet->balance += $referralAmount;
                    $referrerWallet->save();

                    // Update referrer's user balance
                    $referrer->balance += $referralAmount;
                    $referrer->save();

                    // Update referral record
                    $referral->reward_status = 'paid';
                    $referral->amount = $referralAmount;
                    $referral->save();
                    
                    
                   //  Update or create a funds record
            FundsRecord::updateOrCreate(
            [
                'user_id' => $referrer->id,
                'referral_id' => $referral->id,
                'type' => 'referral_commission',
                'pending' => $referralAmount,
            
            ],
            [
                'pending' => 0,
                'earned' => $referralAmount,
            ]
        );

                    // Record transaction for referral reward
                    Transaction::create([
                        'user_id'     => $referrer->id,
                        'amount'      => $referralAmount,
                        'type'        => 'credit',
                        'status'      => 'successful',
                        'description' => 'Referrer reward',
                        'payment_source' => 'system',
                        'category'    => 'referral_commission',
                    ]);

                    // Fire wallet-updated event for the referrer
                    //event(new UserWalletUpdated($referrer->id, $referrer->balance));


                }
            }


    return response()->json([
        'message'           => 'Advert setup fee paid successfully.',
        'balance'           => $wallet->balance,
        'has_paid_advert_fee' => true,
    ], 200);
}


//track all available adverts for users to pick from

public function showAll()
{
    $adverts = $this->AdvertiseRepository->showAll(); // fetch all adverts
    
    if ($adverts->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No available Adverts found at the moment',
        ], 404);
    }

    foreach ($adverts as $advert) {
        // Format created_at
        $advert->created_at_human = $advert->created_at->diffForHumans();

        // Calculate completion percentage if campaign tracking exists
        $total = $advert->task_count_total ?? 0;
        $remaining = $advert->task_count_remaining ?? 0;
        $completed = $total - $remaining;

        $completionPercentage = ($total > 0)
            ? round(($completed / $total) * 100, 2)
            : 0;

        $advert->completion_percentage = $completionPercentage;

        // Mark as completed/available if relevant
        $advert->completed = ($advert->completed == 1) ? 'Completed' : 'Available';

        // Check if advert is new (posted within last 12 hours)
        $hoursDifference = $advert->created_at->diffInHours(now());
        $advert->posted_status = ($hoursDifference < 12) ? 'New Advert' : '';
    }

    return response()->json([
        'status' => true,
        'message' => 'Adverts retrieved successfully',
        'data' => $adverts,
    ], 200);
}

//track advert by ID for users to see details

public function showAds($id)
{
    $advert = $this->AdvertiseRepository->show($id);

    if (!$advert) {
        return response()->json([
            'status' => false,
            'message' => 'Advert not found',
        ], 404);
    }

    // Calculate how many hours since advert was created
    $createdAt = $advert->created_at;
    $now = now();
    $hoursDifference = $createdAt->diffInHours($now);
    $newStatus = ($hoursDifference < 12) ? 'new' : '';

    // Compute advert completion percentage if tracking metrics exist
    $totalCount = $advert->task_count_total ?? 0;
    $remainingCount = $advert->task_count_remaining ?? 0;
    $completedCount = $totalCount - $remainingCount;

    $completionPercentage = ($totalCount > 0)
        ? round(($completedCount / $totalCount) * 100, 2)
        : 0;

    // Attach computed properties
    $advert->completion_percentage = $completionPercentage;
    $advert->completed = ($advert->completed == 1) ? 'Completed' : 'Available';
    $advert->posted_status = $newStatus;

    return response()->json([
        'status' => true,
        'message' => 'Advert retrieved successfully',
        'data' => $advert,
    ], 200);
}


//submit advert for review after completion

public function submitAdvert(Request $request, $id)
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

    return $this->AdvertiseRepository->submitAdvert($request, $id);
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

    $result = $this->AdvertiseRepository->updateParticipantStatus($id, $status);

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
