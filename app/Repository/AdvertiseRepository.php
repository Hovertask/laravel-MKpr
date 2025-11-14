<?php
namespace App\Repository;

use DB;
use App\Models\Advertise;
use Illuminate\Http\Request;
use App\Repository\IAdvertiseRepository;
use App\Models\CompletedTask;
use App\Models\FundsRecord;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Services\FileUploadService;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class AdvertiseRepository implements IAdvertiseRepository
{
    public function index()
    {
        return Advertise::with('advertiseImages')->latest()->get();
    }


    //track all available adverts for users to pick from
    public function showAll() 
    {
        $user = auth()->user();
         return Advertise::with('advertiseImages')->latest()->get();
    }



    // track advert by ID picked by  user/earner  to see details and apply for it 
     public function showads($id)
    {
        
        return Advertise::find($id);
    }

    //track single  advert by id picked  by the advert creator  to track perfomance

    public function show($id)
{
    $ads = Advertise::with([
        'user',
        'advertiseImages',
        'completedTasks.user' // include user details on allocations
    ])->findOrFail($id);

    return $ads;


}


    public function create(array $data, Request $request)
{
    $user = auth()->user();

    $createAds = Advertise::create([
    'user_id' => $user->id,
    'title' => $data['title'] ?? null,
    'platforms' => $data['platforms'] ?? null,
    'gender' => $data['gender'] ?? null,
    'religion' => $data['religion'] ?? null,
    'location' => $data['location'] ?? null,
    'no_of_status_post' => $data['no_of_status_post'] ?? null,
    'payment_method' => $data['payment_method'] ?? null,
    'description' => $data['description'] ?? null,
    'number_of_participants' => $data['no_of_status_post'] ?? null,
    'payment_per_task' => $data['payment_per_task'] ?? null,
    'estimated_cost' => $data['estimated_cost'] ?? null,
    'status' => 'pending',
    'payment_gateway' => 'paystack',
    'deadline' => $data['deadline'] ?? null,
    'task_count_total' => $data['no_of_status_post'],
    'task_count_remaining' => $data['no_of_status_post'],
]);


    // ✅ File uploads (unchanged)
    if ($request->hasFile('file_path')) {
        $files = $request->file('file_path');
        $files = is_array($files) ? $files : [$files];

        foreach ($files as $file) {
            if ($file->isValid()) {
                $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                    'folder' => 'Adverts'
                ]);

                $createAds->advertiseImages()->create([
                    'file_path' => $uploadedFile->getSecurePath(),
                    'public_id' => $uploadedFile->getPublicId(),
                    'media_type' => 'image',
                ]);
            }
        }
    }

    if ($request->hasFile('video_path')) {
        $videos = $request->file('video_path');
        $videos = is_array($videos) ? $videos : [$videos];

        foreach ($videos as $video) {
            if ($video->isValid()) {
                $uploadedFile = Cloudinary::upload($video->getRealPath(), [
                    'folder' => 'adverts',
                    'resource_type' => 'video'
                ]);

                $createAds->advertiseImages()->create([
                    'video_path' => $uploadedFile->getSecurePath(),
                    'public_id' => $uploadedFile->getPublicId(),
                    'media_type' => 'video',
                ]);
            }
        }
    }


    // ✅ Handle payment via wallet

    if ($request->payment_method === 'wallet') {
    // Deduct estimated cost from user's wallet
    $wallet = Wallet::firstOrCreate(
        ['user_id' => $user->id],
        ['balance' => 0]
    );


    if ($wallet->balance < $data['estimated_cost']) {
        throw new \Exception('Insufficient wallet balance to create advert.');
    }

    $wallet->decrement('balance', $data['estimated_cost']);

    // Also deduct from user's main balance
    $user->decrement('balance', $data['estimated_cost']);

    //log transaction
    \App\Models\Transaction::create([
        'user_id'    => $user->id,
        'amount'     => $data['estimated_cost'],
        'type'       => 'debit',
        'status'     => 'success',
        'description'=> 'paid for Advert creation via Wallet',
        'payment_source' => 'wallet',
        'category'    => 'Advert',

    ]);

    // fund record for advert creation
    FundsRecord::create([
        'user_id' => $user->id,
        'advert_id' => $createAds->id,
        'spent' => $data['estimated_cost'],
        'type' => 'Advert',
    ]);

    // update advert status to approved since wallet payment is instant 
    $createAds->update(['status' => 'success']);

}

    return $createAds;
}

  //track all advert created by auth user for management/perfomance tracking

    public function authUserAds()
{
    $user = auth()->user();

    $userAds = Advertise::with('user')
        ->where('user_id', $user->id) // filter ads by current user
        ->get();

    return $userAds;
}

    public function updateAds(array $data, $request, int $id)
    {
        $updateAds = Advertise::find($id);
        $allowedFields = [
            'title', 'platforms', 'gender', 'religion', 'location',
            'no_of_status_post', 'payment_method', 'description',
        ];
        
        $updateAds->update(array_intersect_key($data, array_flip($allowedFields)));

        if ($request->hasFile('file_path')) {
            //dd($request->file('file_path'));
            $files = $request->file('file_path');
        
            // Normalize to array (even if it's one file)
            $files = is_array($files) ? $files : [$files];
        
            foreach ($files as $file) {
                if ($file->isValid()) {
                    $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                        'folder' => 'Adverts'
                    ]);
        
                    $updateAds->advertiseImages()->update([
                        'file_path' => $uploadedFile->getSecurePath(),
                        'public_id' => $uploadedFile->getPublicId()
                    ]);
                }
            }
        }
        
        
        if ($request->hasFile('video_path')) {
            $videos = $request->file('video_path');
            $videos = is_array($videos) ? $videos : [$videos];
        
            foreach ($videos as $video) {
                if ($video->isValid()) {
                    $uploadedFile = Cloudinary::upload($video->getRealPath(), [
                        'folder' => 'adverts',
                        'resource_type' => 'video'
                    ]);
        
                    $updateAds->advertiseImages()->update([
                        'video_path' => $uploadedFile->getSecurePath(),
                        'public_id' => $uploadedFile->getPublicId()
                    ]);
                }
            }
        }

        
        return $updateAds;
    }

    public function approveAds($data, $id)
    {

        $ads = Advertise::find($id);
        $update = $ads->update(['admin_approval_status' => $data['admin_approval_status']]);
        return $update;
    }

    public function destroy($id)
    {
        $ads = Advertise::find($id);
        $deletedAds = $ads->delete();

        return $deletedAds;
    }


public function submitAdvert(Request $request, $id)
{
    DB::beginTransaction();

    try {
        $advert = Advertise::findOrFail($id);
        $userId = auth()->id();

        // 🧩 Check if the user already submitted this advert
        $existingSubmission = CompletedTask::where('user_id', $userId)
            ->where('advert_id', $advert->id)
            ->exists();

        if ($existingSubmission) {
            // ❌ Stop here, do not create new record
            return response()->json([
                'status' => false,
                'type' => 'duplicate', // 👈 helpful for frontend modal
                'message' => 'You have already submitted proof for this advert.',
            ], 400);
        }

        // 🧩 Check if advert is still available
        if ($advert->task_count_remaining <= 0) {
            return response()->json([
                'status' => false,
                'type' => 'unavailable',
                'message' => 'This advert is no longer available.',
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
                    'folder' => 'adverts',
                    'resource_type' => $resourceType,
                ]
            );

            $screenshotPath = $upload->getSecurePath();
        }

        // 🧩 Decrement available slots
        $advert->decrement('task_count_remaining');

        // 🧩 Save completed advert record
        
$completedTask = CompletedTask::create([
    'user_id' => $userId,
    'advert_id' => $advert->id,
    'social_media_url' => $request->input('social_media_url'),
    'screenshot' => $screenshotPath,
    'payment_per_task' => $advert->payment_per_task,
    'title' => $advert->title,
]);

// 🧩 Record pending funds
FundsRecord::create([
    'user_id' => $userId,
    'completed_task_id' => $completedTask->id,
    'pending' => $advert->payment_per_task,
    'type' => 'advert',
]);


        DB::commit();

        return response()->json([
            'status' => true,
            'type' => 'success',
            'message' => 'Advert submitted successfully and is pending review.',
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
public function updateParticipantStatus($id, $status)
{
    try {
        DB::beginTransaction();

        $task = CompletedTask::where('id', $id)->first();

        if (!$task) {
            return [
                'success' => false,
                'message' => 'Task not found',
                'code' => 404
            ];
        }

        if ($task->status !== 'pending') {
            return [
                'success' => false,
                'message' => 'Task already processed',
                'code' => 400
            ];
        }

        $task->update(['status' => $status]);

        // Handle approval logic
        if ($status === 'accepted') {
            $amount = $task->advert->payment_per_task;
            $advertOwnerId = $task->user_id;

            // ✅ Update wallet
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $advertOwnerId],
                ['balance' => 0]
            );
            $wallet->increment('balance', $amount);

            // ✅ Update user balance
            $user = User::find($advertOwnerId);
            if ($user) {
                $user->increment('balance', $amount);
            }

            // ✅ Record earning
            FundsRecord::updateOrCreate(
                [
                    'user_id' => $advertOwnerId,
                    'completed_task_id' => $task->id,
                    'type' => 'advert',
                ],
                [
                    'pending' => 0,
                    'earned' => $amount,
                ]
            );

            

            DB::commit();

            return [
                'success' => true,
                'message' => 'Task approved successfully',
                'data' => $task,
                'code' => 200
            ];
        }

        // Handle rejection
        if ($status === 'rejected') {
            DB::commit();

            return [
                'success' => true,
                'message' => 'Task rejected successfully',
                'data' => $task,
                'code' => 200
            ];
        }

    } catch (\Exception $e) {
        DB::rollBack();
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'code' => 500
        ];
    }
}



}