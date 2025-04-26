<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\IAdvertiseRepository;
use Illuminate\Support\Facades\Validator;

class AdvertiseController extends Controller
{
    public $AdvertiseRepository;
    public function __construct(IAdvertiseRepository $AdvertiseRepository)
    {
        $this->AdvertiseRepository = $AdvertiseRepository;
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
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'religion' => 'nullable|max:255',
            'location' => 'nullable|max:255',
            'gender' => 'nullable|max:20',
            'platforms' => 'required',
            'no_of_status_post' => 'nullable|integer',
            // 'file_path' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
            // 'video_path' => 'nullable|file|mimes:mp4,mov,avi,gif|max:10240',
            'file_path' => 'nullable',
            'video_path' => 'nullable',
            'description' => 'required|string|min:20',
            'payment_method' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $createAds = $this->AdvertiseRepository->create($request->all(), $request);

        return response()->json([
            'status' => true,
            'Message' => 'Ads Created successfully',
            'data' => $createAds,
        ]);
    }

    public function show($id)
    {
        $showAds = $this->AdvertiseRepository->show($id);

        if(!$showAds)
        {
            return response()->json([
                'status' => false,
                'Mesaage' => 'Ads not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Ads retrieved successfully',
            'data' => $showAds,
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
}
