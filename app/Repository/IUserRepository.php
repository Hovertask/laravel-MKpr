<?php

namespace App\Repository;

use App\Models\User;

interface IUserRepository
{
    public function create(array $data): User;
    public function sendPasswordResetLink(string $email): string;
    public function login(array $credentials);
    public function resetPassword(array $data);
    public function logout(User $user);
}

