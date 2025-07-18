<?php

namespace App\Repository;

use App\Models\User;

interface IUserRepository
{
    public function create(array $data): User;
    public function sendPasswordResetLink(string $email): string;
    public function login(array $credentials);
    public function updateProfile(array $data);
    public function resetPassword(array $data);
    public function changePassword(array $data);
    public function banks(array $data);
    public function logout(User $user);
    public function roles();
}

