<?php

namespace App\Repository;

use Illuminate\Http\Request;

interface IAddMeUpRepository
{
    public function index();
    public function create($whatsapp_number);
    public function addMeUp(int $added_user_id);
    public function listContact(array $data, Request $request);
    public function listGroup(array $data, Request $request);
    public function myList();
}