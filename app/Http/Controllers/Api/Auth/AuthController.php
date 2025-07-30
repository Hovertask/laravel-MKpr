<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use App\Mail\WelcomeMail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Repository\IUserRepository;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use App\Notifications\BankInfomationUpdatedNotification;

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
            'country' => 'required|string|max:255',
            'currency' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'avatar' => 'nullable|string|max:255',
            'referal_username' => 'nullable|string|max:255',
            //'referral_code' => 'nullable|string|max:255',
            'role_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        
        DB::beginTransaction();

        $validatedData = $validator->validated();

        // if (isset($validatedData['referral_code'])) {
        //     $referrer = User::where('referral_code', $validatedData['referral_code'])->first();
        //     if ($referrer) {
        //         //dd($referrer);
        //         $validatedData['referred_by'] = $referrer->id;
        //         $validatedData['referral_username'] = $referrer->username;
        //     }
        // }


        $user = $this->user->create($validatedData);
        $user->sendEmailVerificationNotification();
        $user->addRole($validatedData['role_id']);
        $token = $user->createToken('API Token')->plainTextToken;
        Mail::to($user->email)->send(new WelcomeMail($user));

        DB::commit();
       if($user)
       {
            return response()->json([
                'status' => true,
                'message' => 'User registered successfully, Kindly verify your email',
                'data' => $user,
                'token' => $token,
            ], 201);
       }
       else{
            return response()->json([
                'status' => false,
                'message' => 'User registration failed',
            ]);
       }
       
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

//     public function updateProfile(Request $request)
// {
//     $user = auth()->user();

//     $validator = Validator::make($request->all(), [
//         'fname' => 'sometimes|string|max:255',
//         'lname' => 'sometimes|string|max:255',
//         'email' => 'sometimes|string|email|max:255',
//         'country' => 'sometimes|string|max:255',
//         'currency' => 'sometimes|string|max:255',
//         'phone' => 'sometimes|string|max:255',
//         'avatar' => 'nullable|string|max:255',
//     ]);

//     if ($validator->fails()) {
//         return response()->json([
//             'status' => false,
//             'message' => 'Validation error',
//             'errors' => $validator->errors()
//         ], 400);
//     }

//     $validatedData = $validator->validated();
//     //dd($validatedData); 

//     $update = $user->update($validatedData);

//     if ($update) {
//         return response()->json([
//             'success' => true,
//             'message' => 'Profile updated successfully'
//         ]);
//     }

//     return response()->json([
//         'success' => false,
//         'message' => 'Profile update failed'
//     ], 500);
// }

    public function updateProfile(Request $request)
    {
       // dd($request->all)
        $validator = Validator::make($request->all(), [
             'fname' => 'sometimes|string|max:255',
            'lname' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255',
            'country' => 'sometimes|string|max:255',
            'currency' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:255',
            'avatar' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        $validatedData = $validator->validated();

        //dd($validatedData);

        $user = $this->user->updateProfile($validatedData);

        //dd($user);

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => $user
        ], 200);
    }


public function updatePassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        'password' => 'required|string|min:6|confirmed',
        'old_password' => 'required|string|min:6',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 400);
    }

    $user = auth()->user();

    // ✅ Check if old password is correct
    if (!Hash::check($request->old_password, $user->password)) {
        return response()->json([
            'status' => false,
            'message' => 'Old password is incorrect',
        ], 403);
    }

    $user->update([
        'password' => Hash::make($request->password),
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Password updated successfully',
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

public function showResetForm($token)
{
    return response()->json(['token' => $token]);
}

// Handle the password reset
public function resetPasswordPost(Request $request)
{
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|confirmed|min:8',
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->setRememberToken(Str::random(60));

            $user->save();

            event(new PasswordReset($user));
        }
    );

    return $status === Password::PASSWORD_RESET
        ? response()->json(['message' => 'Password reset successfully'], 200)
        : response()->json(['message' => 'Unable to reset password'], 400);
}

public function changePassword(Request $request)
{
    $user = Auth::user();
    $validator = Validator::make($request->all(), [
        'current_password' => 'required|string|min:6',
        'new_password' => 'required|string|min:6|confirmed',
        'confirm_password' => 'required|string|min:6',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,   
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 400);
    }

    $validatedData = $validator->validated();

    $changePass = $this->user->changePassword($validatedData);

    if ($changePass) {
        return response()->json([
            'status' => true,
            'message' => 'Password changed successfully',
        ]);
        
    } else {
        return response()->json([
            'status' => false,
            'message' => 'Unable to change password',
        ], 500);
    }
}

public function banks(Request $request)
{
    $user = Auth::user();
    $validator = Validator::make($request->all(),[
        'bank_name' => 'required|string|max:140',
        'account_name' => 'nullable|string',
        'account_number' => 'required|numeric'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,   
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 400);
    }

    $validatedData = $validator->validated();

    $banks = $this->user->banks($validatedData);

    $user->notify(new BankInfomationUpdatedNotification($user));

    return response()->json([
        'status' => true,
        'message' => 'Bank details updated successfully'
    ]);
    
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

    public function roles()
    {
        return response()->json([
            'status' => true,
            'data' => $this->user->roles(),
        ], 200);
    }
}
