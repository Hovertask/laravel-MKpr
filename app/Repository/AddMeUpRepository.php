<?php

namespace App\Repository;

use Exception;
use Carbon\Carbon;
use App\Models\Wallet;
use App\Models\Contact;
use App\Models\ContactList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class AddMeUpRepository implements IAddMeUpRepository
{
    public function index()
    {
        try {
            return Contact::where('list_expire_date', '>', Carbon::now())
            ->where('contact_type', 'listed_contact')
            ->where('status', 'approved')
            ->get();
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
    public function create($whatsapp_number)
    {
        try {
            $contact = Contact::create([
                'whatsapp_number' => $whatsapp_number,
                'user_id' => Auth::user()->id
            ]);
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function addMeUp(int $added_user_id)
    {
        try {
            $getPointBalance = Wallet::where('user_id', Auth::user()->id)->first();
            if($getPointBalance == null || $getPointBalance->points < 10) {
                return response()->json(['error' => 'You do not have enough points to add this user'], 400);
            }
            else {
                $getPointBalance->decrement('points', 10);
            
                $addUp = ContactList::create([
                    'user_id' => Auth::user()->id,
                    'added_user_id' => $added_user_id,
                ]);
            }
            return $addUp;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function listContact(array $data, Request $request)
    {
        try {
            $listContact = Contact::updateOrCreate(
                [
                    //allow user to list contact mutiple times wit
                    'user_id' => Auth::user()->id,
                    'whatsapp_number' => $data['whatsapp_number']
                ],
                [
                    'display_name' => $data['display_name'],
                    'listing_type' => $data['listing_type'],
                    'how_you_want_your_profile_listed' => $data['how_you_want_your_profile_listed'],
                    'how_long_you_want_your_profile_listed' => $data['how_long_you_want_your_profile_listed'],
                    'gender' => $data['gender'],
                    'where_you_want_your_contacts_from' => $data['where_you_want_your_contacts_from'],
                    'list_expire_date' => Carbon::now()->addDays((int) $data['how_long_you_want_your_profile_listed']),
                    'contact_type' => 'listed_contact',
                ]
            );
    
            if ($request->hasFile('display_picture')) {
                $file = $request->file('display_picture');
    
                $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                    'folder' => 'AddMeUp',
                ]);
    
                $filename = $uploadedFile->getSecurePath();
    
                $listContact->update([
                    'display_picture' => $filename
                ]);
            }
    
            return $listContact;
    
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }    

    public function listGroup(array $data, Request $request)
    {
        try {
            $listContact = Contact::updateOrCreate(
                [
                    'user_id' => Auth::user()->id,
                    'whatsapp_number' => $data['whatsapp_number']
                ],
                [
                    'display_name' => $data['display_name'],
                    'listing_type' => $data['listing_type'],
                    'how_you_want_your_profile_listed' => $data['how_you_want_your_profile_listed'],
                    'how_long_you_want_your_profile_listed' => $data['how_long_you_want_your_profile_listed'],
                    'gender' => $data['gender'],
                    'where_you_want_your_contacts_from' => $data['where_you_want_your_contacts_from'],
                    'contact_type' => 'listed_group',
                ]
            );
    
            if ($request->hasFile('display_picture')) {
                $file = $request->file('display_picture');
    
                $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                    'folder' => 'AddMeUp',
                ]);
    
                $filename = $uploadedFile->getSecurePath();
    
                $listContact->update([
                    'display_picture' => $filename
                ]);
            }
    
            return $listContact;
    
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function myList()
    {
        try {
            $myList = ContactList::with('user', 'addedUser')->where('user_id', Auth::user()->id)->get();
            return $myList;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

}