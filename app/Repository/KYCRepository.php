<?php

namespace App\Repository;

use Exception;
use App\Models\KYC;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\KYCApprovalNotification;
use App\Notifications\KYCSubmittedNotification;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class KYCRepository
{
    public function show(int $id)
    {
        return KYC::find($id);
    }
    public function submit(Request $request, int $id)
    {
        dd($request->all());
        DB::beginTransaction();
        try {
            $check = KYC::where(['user_id' => $id])->exists();
            if ($check) {
                return response()->json([
                    'status' => false,
                    'message' => 'KYC already submitted, kindly update your KYC instead',
                ]);
            }
            $kycData = [
                'user_id' => $id,
                'full_name' => $request['full_name'],
                'dob' => $request['dob'],
                'country' => $request['country'],
                'document_type' => $request['document_type'],
                'document_number' => $request['document_number'],
                'document_expiry' => $request['document_expiry'],
            ];

            $kyc = KYC::create($kycData);

            if ($request->hasFile('document_front_image') && $request->file('document_front_image')->isValid()) {
                $uploadedFile = Cloudinary::upload($request->file('document_front_image')->getRealPath(), [
                    'folder' => 'KYC'
                ]);
                $kyc->update([
                    'document_front_image' => $uploadedFile->getSecurePath()
                ]);
            }


            if ($request->hasFile('document_back_image') && $request->file('document_back_image')->isValid()) {
                $uploadedFile = Cloudinary::upload($request->file('document_back_image')->getRealPath(), [
                    'folder' => 'KYC'
                ]);
                $kyc->update([
                    'document_back_image' => $uploadedFile->getSecurePath()
                ]);
            }

            
            if ($request->hasFile('user_selfie_image') && $request->file('user_selfie_image')->isValid()) {
                $uploadedFile = Cloudinary::upload($request->file('user_selfie_image')->getRealPath(), [
                    'folder' => 'KYC'
                ]);
                $kyc->update([
                    'user_selfie_image' => $uploadedFile->getSecurePath()
                ]);
            }
            $user = auth()->user();
            $user->notify(new KYCSubmittedNotification($kyc));
            DB::commit();
            
            return $kyc->fresh();
        }
        catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
    }

    public function update(array $data, Request $request, int $id)
    {
        $user = auth()->user();
        //dd($id);
        $kyc = KYC::find($id);
       // dd($kyc);
        DB::beginTransaction();
        try{

            $kyc->update([
                'full_name' => $request['full_name'],
                'dob' => $request['dob'],
                'country' => $request['country'],
                'document_type' => $request['document_type'],
                'document_number' => $request['document_number'],
                'document_expiry' => $request['document_expiry'],
        ]);

            if ($request->hasFile('document_front_image') && $request->file('document_front_image')->isValid()) {
                $uploadedFile = Cloudinary::upload($request->file('document_front_image')->getRealPath(), [
                    'folder' => 'KYC'
                ]);
                $kyc->update([
                    'document_front_image' => $uploadedFile->getSecurePath()
                ]);
            }


            if ($request->hasFile('document_back_image') && $request->file('document_back_image')->isValid()) {
                $uploadedFile = Cloudinary::upload($request->file('document_back_image')->getRealPath(), [
                    'folder' => 'KYC'
                ]);
                $kyc->update([
                    'document_back_image' => $uploadedFile->getSecurePath()
                ]);
            }

            if ($request->hasFile('user_selfie_image') && $request->file('user_selfie_image')->isValid()) {
                $uploadedFile = Cloudinary::upload($request->file('user_selfie_image')->getRealPath(), [
                    'folder' => 'KYC'
                ]);
                $kyc->update([
                    'user_selfie_image' => $uploadedFile->getSecurePath()
                ]);
            }
            
            DB::commit();
            return $kyc->fresh();

        } catch(Exeption $e) {
            DB::rollBack();
            throw $e;
        }

    }

    public function approve(int $id, int $userId)
    {
        $kyc = KYC::find($id);

        $kyc->update([
            'status' => 'approved',
            'approved_by' => $userId
        ]);

        $user = User::find($kyc->user_id);
        $user->notify(new KYCApprovalNotification($user));

        return $kyc->fresh();
    }
    public function reject(int $id, int $userId)
    {
        $kyc = KYC::find($id);

        $kyc->update([
            'status' => 'rejected',
            'approved_by' => $userId
        ]);
    }
}   