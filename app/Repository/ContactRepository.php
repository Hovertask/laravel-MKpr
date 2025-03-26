<?php
namespace App\Repository;

use App\Models\Contact;
use App\Models\ContactList;
use Illuminate\Support\Facades\Auth;
use App\Repository\IContactRepository;

Class ContactRepository implements IContactRepository
{
    public function createContact(array $data)
    {
        $user = Auth::user();
    
        $contact = Contact::updateOrCreate(
            [
                'user_id' => $user->id,
                'whatsapp_number' => $data['whatsapp_number'],
            ],
            [
                'display_name' => $data['display_name'],
                'listing_type' => $data['listing_type'],
                'how_you_want_your_profile_listed' => $data['how_you_want_your_profile_listed'], // Fixed typo
                'how_long_you_want_your_profile_listed' => $data['how_long_you_want_your_profile_listed'],
                'gender' => $data['gender'],
                'where_you_want_your_contacts_from' => $data['where_you_want_your_contacts_from'],
                'display_picture' => $data['display_picture'],
                'contact_type' => 'Contact',
                'status' => $data['status'],
            ]
        );
    
        return $contact;
    }

    public function createGroup(array $data)
    {
        $user = Auth::user();
    
        $contact = Contact::updateOrCreate(
            [
                'user_id' => $user->id,
                'whatsapp_number' => $data['whatsapp_number'],
            ],
            [
                'display_name' => $data['display_name'],
                'listing_type' => $data['listing_type'],
                'how_you_want_your_profile_listed' => $data['how_you_want_your_profile_listed'],
                'how_long_you_want_your_profile_listed' => $data['how_long_you_want_your_profile_listed'],
                'gender' => $data['gender'],
                'where_you_want_your_contacts_from' => $data['where_you_want_your_contacts_from'],
                'display_picture' => $data['display_picture'],
                'contact_type' => 'Group',
                'status' => $data['status'],
            ]
        );
    
        return $contact;
    }

    public function addContact($contactId)
    {
        $user = Auth::user();
        //get the id of the contac and also the added contact id- if added succesfully call wallet model and add 10points to the user_id
        $contact = ContactList::create([
            'user_id' => $user->id,
        ]);
    }
}