<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Admin\AdminUserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminUserController extends Controller
{
    protected $userRepo;

    public function __construct(AdminUserRepository $userRepo)
    {
        $this->middleware(['auth:sanctum', 'role:superadministrator']);
        $this->userRepo = $userRepo;
    }

    public function index()
    {
        $users = $this->userRepo->getAllUsers();
        return response()->json($users);
    }

    public function show($id)
    {
        $user = $this->userRepo->getUserById($id);
        return response()->json($user);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fname' => 'required|string',
            'lname' => 'required|string',
            'username' => 'required|string|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string',
            'country' => 'nullable|string',
            'currency' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $this->userRepo->createUser($request->all());
        return response()->json($user, 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'fname' => 'sometimes|string',
            'lname' => 'sometimes|string',
            'username' => 'sometimes|string|unique:users,username,' . $id,
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8',
            'phone' => 'sometimes|string',
            'country' => 'sometimes|string',
            'currency' => 'sometimes|string',
            'balance' => 'sometimes|numeric',
            'account_status' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $this->userRepo->updateUser($id, $request->all());
        return response()->json($user);
    }

    public function destroy($id)
    {
        $this->userRepo->deleteUser($id);
        return response()->json(['message' => 'User deleted']);
    }

    public function ban($id)
    {
        $user = $this->userRepo->banUser($id);
        return response()->json($user);
    }

    public function unban($id)
    {
        $user = $this->userRepo->unbanUser($id);
        return response()->json($user);
    }

    public function getByRole($role)
    {
        $users = $this->userRepo->getUsersByRole($role);
        return response()->json($users);
    }
}