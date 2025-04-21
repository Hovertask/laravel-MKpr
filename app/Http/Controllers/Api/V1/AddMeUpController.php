<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\IAddMeUpRepository;
use Illuminate\Support\Facades\Validator;

class AddMeUpController extends Controller
{
    public $AddMeUpRepository;
    public function __construct(IAddMeUpRepository $AddMeUpRepository)
    {
        $this->AddMeUpRepository = $AddMeUpRepository;
    }

    public function index()
    {
        return $this->AddMeUpRepository->index();
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'whatsapp_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $data = $this->AddMeUpRepository->create($request->whatsapp_number);

        // if(!$data) {
        //     return response()->json(['error' => 'User already added'], 400);
        // }

        return response()->json(['message' => 'User added successfully', 'data' => $data]);
    }

    public function addMeUp(int $added_user_id)
    {
        $addme = $this->AddMeUpRepository->addMeUp($added_user_id);

        if (!$addme) {
            return response()->json(['error' => 'You do not have enough points to add this user'], 400);
        }
    
        return response()->json([
            'status' => true,
            'message' => 'User added successfully', 
            'data' => $addme
        ]);
    }

    public function listContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'whatsapp_number' => 'required|string',
            'display_name' => 'required|string',
            'listing_type' => 'nullable|string',
            'how_you_want_your_profile_listed' => 'required|string',
            'how_long_you_want_your_profile_listed' => 'required|string',
            'gender' => 'required|string',
            'where_you_want_your_contacts_from' => 'required|string',
            'display_picture' => 'required|file|mimes:jpeg,png,jpg,gif|max:2048',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        $data = $this->AddMeUpRepository->listContact($validator->validated(), $request);

        if(!$data) {
            return response()->json(['error' => 'Something went wrong'], 400);
        }

        return response()->json(
            [
                'status' => true,
                'message' => 'Contact Listed successfully', 
                'data' => $data
            ]);
    }

    public function listGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'whatsapp_number' => 'required|string',
            'display_name' => 'required|string',
            'listing_type' => 'nullable|string',
            'how_you_want_your_profile_listed' => 'required|string',
            'how_long_you_want_your_profile_listed' => 'required|string',
            'gender' => 'required|string',
            'where_you_want_your_contacts_from' => 'required|string',
            'display_picture' => 'required|file|mimes:jpeg,png,jpg,gif|max:2048',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        $data = $this->AddMeUpRepository->listGroup($validator->validated(), $request);

        if(!$data) {
            return response()->json(['error' => 'Something went wrong'], 400);
        }

        return response()->json(
            [
                'status' => true,
                'message' => 'Group Listed successfully', 
                'data' => $data
            ]);
    }

    public function myList()
    {
        $list =  $this->AddMeUpRepository->myList();

        if(!$list) {
            return response()->json(['error' => 'Something went wrong'], 400);
        }

        return response()->json(
            [
                'status' => true,
                'message' => 'My List', 
                'data' => $list
            ]);
    }
}
