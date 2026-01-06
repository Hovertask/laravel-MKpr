<?php

namespace App\Repository\Admin;

interface IAdminUserRepository
{
    public function getAllUsers();
    public function getUserById($id);
    public function updateUser($id, array $data);
    public function deleteUser($id);
    public function createUser(array $data);
    public function getUsersByRole($role);
    public function banUser($id);
    public function unbanUser($id);
}