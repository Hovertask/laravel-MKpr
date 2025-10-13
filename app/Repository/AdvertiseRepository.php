<?php
namespace App\Repository;

use App\Models\Advertise;
use Illuminate\Http\Request;
use App\Repository\IAdvertiseRepository;
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
        $Adverts = Advertise::all();
        return $Adverts;
    }



    // track advert by ID for users to see details
     public function showads($id)
    {
        
        return Advertise::find($id);
    }

    //track single advert by id of authenticated user

    public function show($id)
{
    $ads = Advertise::with([
        'user',
        'advertiseImages',
        'userTasks.user' // include user details on allocations
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

                $createAds->advertiseImages()->create([
                    'video_path' => $uploadedFile->getSecurePath(),
                    'public_id' => $uploadedFile->getPublicId()
                ]);
            }
        }
    }

    return $createAds;
}

  //track all advert created by auth user for management

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

}