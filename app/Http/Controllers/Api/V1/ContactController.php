<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\IContactRepository;

class ContactController extends Controller
{
    protected $contactRepository;

    public function __construct(IContactRepository $contactRepository)
    {
        $this->contactRepository = $contactRepository;
    }

    public function createContact(Request $request)
    {
        $data = $request->all();
        $contact = $this->contactRepository->createContact($data);
        return response()->json(['message' => 'Contact created successfully!', 'contact' => $contact]);
    }

    public function createGroup(Request $request)
    {
        $data = $request->all();
        $group = $this->contactRepository->createGroup($data);
        return response()->json(['message' => 'Group created successfully!', 'contact' => $group]);
    }
}
