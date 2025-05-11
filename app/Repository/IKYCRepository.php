<?php
namespace App\Repository;

use App\Models\User;

interface IKYCRepository
{
    public function show(int $id);
    public function submit(Request $request, int $id);
    public function update(array $data, Request $request, int $id);
    public function approve(int $id, int $userId);
    public function reject(int $id);
}