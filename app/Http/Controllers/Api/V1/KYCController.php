<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Repository\KYCRepository;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class KYCController extends Controller
{
    protected $kycRepository;

    public function __construct(KYCRepository $kycRepository)
    {
        $this->kycRepository = $kycRepository;
    }
    public function show($id)
    {
        return $this->kycRepository->show($id);
    }

    public function submit(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string',
            'country' => 'required|string',
            'document_type' => 'required|string',
            'document_number' => 'required|string',
            'document_expiry' => 'required|string',
            'document_front_image' => 'required|mimes:jpeg,png,jpg',
            'document_back_image' => 'required|mimes:jpeg,png,jpg',
            'user_selfie_image' => 'required|mimes:jpeg,png,jpg',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        $validatedData = $validator->validated();

       $kyc = $this->kycRepository->submit($request, $user->id);

       if(!$kyc) {
           return response()->json([
               'status' => false,
               'message' => 'Unable to submit KYC',
           ]);
       }
       else{

        return response()->json([
            'status' => true,
            'message' => 'KYC submitted successfully',
            'data' => $kyc,
        ]);
       }
    }

    public function update(Request $request, int $id)
    {
        $user = auth()->user();
        //dd($id);
        //dd($request->all());
        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|string',
            'country' => 'sometimes|string',
            'document_type' => 'sometimes|string',
            'document_number' => 'sometimes|string',
            'document_expiry' => 'sometimes|string',
            'document_front_image' => 'sometimes|mimes:jpeg,png,jpg,pdf',
            'document_back_image' => 'sometimes|mimes:jpeg,png,jpg,pdf',
            'user_selfie_image' => 'sometimes|mimes:jpeg,png,jpg,pdf',
apache_child_terminate
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        $validatedData = $validator->validated();

        $kycUpdated = $this->kycRepository->update($validator->validated(), $request, $id);

        return response()->json([
            'status' => true,
            'message' => 'KYC updated successfully',
            'data' => $kycUpdated,
        ]);
    }

    public function approve(int $id)
    {
        $userId = auth()->user()->id;
        $kyc = $this->kycRepository->approve($id, $userId);
        return response()->json([
            'status' => true,
            'message' => 'KYC approved successfully',
            'data' => $kyc,
        ]);
    }

}
