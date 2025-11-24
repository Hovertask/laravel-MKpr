<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use App\Models\PasswordResetCode;
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

    
    public function resendEmailVerificationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Delete any existing codes for this user
        \App\Models\EmailVerificationCode::where('user_id', $user->id)->delete();

        // Generate new code
        $verificationCode = random_int(100000, 999999);
        $expiresAt = now()->addMinutes(10);
        \App\Models\EmailVerificationCode::create([
            'user_id' => $user->id,
            'code' => $verificationCode,
            'expires_at' => $expiresAt,
        ]);

        // Send code via notification
        $user->notify(new \App\Notifications\EmailVerificationCodeNotification($verificationCode));

        return response()->json([
            'status' => true,
            'message' => 'Verification code resent successfully',
        ], 200);
    }


    public function verifyEmailCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 400);
        }

        $validated = $validator->validated();
        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        $verification = \App\Models\EmailVerificationCode::where('user_id', $user->id)
            ->where('code', $validated['code'])
            ->where('expires_at', '>', now())
            ->first();

        if (!$verification) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired verification code',
            ], 400);
        }

        // Mark user as verified
        $user->email_verified_at = now();
        $user->save();

        // Delete the used code
        $verification->delete();

        return response()->json([
            'status' => true,
            'message' => 'Email verified successfully',
        ], 200);
    }

public function register(Request $request)
{
    $validator = Validator::make(
        $request->all(),
        [
            'fname'     => 'required|string|max:255',
            'lname'     => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users',
            'username'  => 'required|string|max:255|unique:users',
            'password'  => 'required|string|min:6|confirmed',
            'country'   => 'required|string|max:255',
            'currency'  => 'required|string|max:255',
            'phone'     => 'required|regex:/^[0-9]{7,15}$/|unique:users,phone',
            'avatar'    => 'nullable|string|max:255',
            'referal_username' => 'nullable|string|max:255',
            'referral_code'    => 'nullable|string|max:255',
            //'role_id'   => 'required|integer|max:255',
        ],
        [
            //custom messages
        'phone.regex' => 'Phone number must contain only digits and be between 7–15 digits. Example: 08012345678.',
        'phone.unique' => 'This phone number is already registered.',
        ]
    );

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => $validator->errors()->first(),
            'errors'  => $validator->errors(),
        ], 400);
    }

    DB::beginTransaction();

    $validatedData = $validator->validated();

    //  Check referral by CODE first
    if (!empty($validatedData['referral_code'])) {
        $referrer = User::where('referral_code', $validatedData['referral_code'])->first();
        if ($referrer) {
            $validatedData['referred_by'] = $referrer->id;
            $validatedData['referal_username'] = $referrer->username;
        }
    }
    //  If no code, fallback to USERNAME
    elseif (!empty($validatedData['referal_username'])) {
        $referrer = User::where('username', $validatedData['referal_username'])->first();
        if ($referrer) {
            $validatedData['referred_by'] = $referrer->id;
        }
    }

    // Create user
    $user = $this->user->create($validatedData);

    // Assign role
    //$user->addRole($validatedData['role_id']);

    // Generate auth token
    $token = $user->createToken('API Token')->plainTextToken;

    // Send Welcome Email
    Mail::to($user->email)->send(new WelcomeMail($user));

    // Generate & send verification code
    $verificationCode = random_int(100000, 999999);
    \App\Models\EmailVerificationCode::create([
        'user_id'    => $user->id,
        'code'       => $verificationCode,
        'expires_at' => now()->addMinutes(10),
    ]);
    $user->notify(new \App\Notifications\EmailVerificationCodeNotification($verificationCode));

    DB::commit();

    return response()->json([
        'status'  => true,
        'message' => 'User registered successfully. Kindly verify your email.',
        'data'    => $user,
        'token'   => $token,
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
                'message' => $validator->errors()->first(),
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
                'message' => $validator->errors()->first(),
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
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 400);
    }

    $user = auth()->user();

    //  Check if old password is correct
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
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 400);
        }

        $validatedData = $validator->validated();
        $email = $validatedData['email'];

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Generate a 6-digit code
        $code = rand(100000, 999999);
        $expiresAt = now()->addMinutes(10);
        // Store or update the code
        \App\Models\PasswordResetCode::updateOrCreate(
            ['email' => $email],
            ['code' => $code, 'expires_at' => $expiresAt]
        );

        // Send code via email (simple example, you may want a notification class)
        Mail::raw("Your password reset code is: $code", function ($message) use ($email) {
            $message->to($email)
                ->subject('Your Password Reset Code');
        });

        return response()->json([
            'status' => true,
            'message' => 'Password reset code sent to your email',
        ]);
    }

public function showResetForm($token)
{
    return response()->json(['token' => $token]);
}

// Handle the password reset


public function resetPasswordPost(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'code' => 'required|string',
        'password' => 'required|string|min:8|confirmed',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422);
    }

    $reset = PasswordResetCode::where('email', $request->email)
        ->where('code', $request->code)
        ->where('expires_at', '>', now())
        ->first();

    if (!$reset) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid or expired code',
        ], 400);
    }

    $user = User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'User not found',
        ], 404);
    }

    $user->forceFill([
        'password' => Hash::make($request->password)
    ])->setRememberToken(Str::random(60));

    $user->save();

    // Delete the used code
    $reset->delete();

    event(new \Illuminate\Auth\Events\PasswordReset($user));

    return response()->json([
        'status' => true,
        'message' => 'Password reset successfully',
    ], 200);
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
            'message' => $validator->errors()->first(),
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
            'message' => $validator->errors()->first(),
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

