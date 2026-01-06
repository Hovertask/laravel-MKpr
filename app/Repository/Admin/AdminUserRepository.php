<?php

namespace App\Repository\Admin;

use App\Models\User;
use App\Repository\Admin\IAdminUserRepository;
use Illuminate\Support\Facades\Hash;

class AdminUserRepository implements IAdminUserRepository
{
    public function getAllUsers()
    {
        return User::with('roles')->paginate(20);
    }

    public function getUserById($id)
    {
        return User::with('roles')->findOrFail($id);
    }

    public function updateUser($id, array $data)
    {
        $user = User::findOrFail($id);
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);
        return $user;
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return true;
    }

    public function createUser(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        return User::create($data);
    }

    public function getUsersByRole($role)
    {
        return User::whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role);
        })->with('roles')->paginate(20);
    }

    public function banUser($id)
    {
        $user = User::findOrFail($id);
        $user->update(['account_status' => 'banned']);
        return $user;
    }

    public function unbanUser($id)
    {
        $user = User::findOrFail($id);
        $user->update(['account_status' => 'active']);
        return $user;
    }
}