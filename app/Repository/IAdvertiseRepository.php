<?php

namespace App\Repository;

use Illuminate\Http\Request;

interface IAdvertiseRepository
{
    public function index();
    public function create(array $data, Request $request);
    public function show($id);
    public function showAll();
    public function authUserAds();
    public function approveAds($data, $id);
    public function updateAds(array $data, Request $request, int $id);
    public function destroy($id);
}