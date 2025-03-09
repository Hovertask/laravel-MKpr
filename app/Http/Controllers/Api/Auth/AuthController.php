<?php

namespace App\Http\Controllers\Api\Auth;

use App\Mail\WelcomeMail;
use Illuminate\Http\Request;
use App\Repository\IUserRepository;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $user;
    public function __construct(IUserRepository $user)
    {
        $this->user = $user;
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'how_you_want_to_use' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'currency' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'avatar' => 'nullable|string|max:255',
            'referal_username' => 'nullable|string|max:255',
            'referal_code' => 'nullable|string|max:255',
            'role_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        $validatedData = $validator->validated();
        $user = $this->user->create($validatedData);
        $user->addRole($validatedData['role_id']);
        $token = $user->createToken('API Token')->plainTextToken;
        Mail::to($user->email)->send(new WelcomeMail($user));

        return response()->json([
            'status' => true,
            'message' => 'User registered successfully',
            'data' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 400);
        }

        $credentials = $validator->validated();
        $authData = $this->user->login($credentials);

        if (!$authData) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'data' => $authData['user'],
            'token' => $authData['token'],
        ], 200);
    }


    public function resetPassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,   
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 400);
    }

    $validatedData = $validator->validated();

    $status = Password::sendResetLink(['email' => $validatedData['email']]);

    if ($status === Password::RESET_LINK_SENT) {
        return response()->json([
            'status' => true,
            'message' => 'Password reset link sent to your email',
        ]);
    }

    return response()->json([
        'status' => false,
        'message' => 'Unable to send password reset link',
    ], 500);
}


    // public function resetPassword(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'email' => 'required|email|exists:users,email',
    //         'token' => 'required',
    //         'password' => 'required|string|min:6|confirmed',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Validation error',
    //             'errors' => $validator->errors(),
    //         ], 400);
    //     }

    //     $status = $this->user->resetPassword($validator->validated());

    //     if ($status === Password::PASSWORD_RESET) {
    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Password reset successful. You can now log in.',
    //         ], 200);
    //     } else {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Invalid or expired token',
    //         ], 400);
    //     }
    // }

    public function logout(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $this->user->logout($user);

        return response()->json([
            'status' => true,
            'message' => 'Logout successful',
        ], 200);
    }
}
