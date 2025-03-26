<?php
namespace App\Repository;

use App\Models\User;
use App\Models\Referral;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class UserRepository implements IUserRepository
{
    public function create(array $data): User
    {
        //this is for string, keeping for future use
        // do {
        //     $referralCode = Str::upper(Str::random(8));
        // } while (User::where('referral_code', $referralCode)->exists());

        do {
            $referralCode = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (User::where('referral_code', $referralCode)->exists());


        $user = User::create([
            'fname' => $data['fname'],
            'lname' => $data['lname'],
            'email' => $data['email'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'how_you_want_to_use' => $data['how_you_want_to_use'],
            'country' => $data['country'],
            'currency' => $data['currency'],
            'phone' => $data['phone'],
            'avatar' => $data['avatar'] ?? null,
            'referred_by' => $data['referred_by'] ?? null,
            'referral_code' => $referralCode ?? null,
        ]);
        
        if (isset($data['referred_by'])) {
            $this->trackReferral($data['referred_by'], $user->id);
        }

        return $user;
    }

    
    protected function trackReferral(int $referrerId, int $referreeId): void
    {
        Referral::create([
            'referrer_id' => $referrerId,
            'referee_id' => $referreeId,
            'reward_status' => 'pending',
        ]);
    }

    public function login(array $credentials)
    {
        if (!Auth::attempt($credentials)) {
            return null;
        }

        $user = Auth::user();
        $token = $user->createToken('API Token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    public function sendPasswordResetLink(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);

        return $status;
    }

    public function resetPassword(array $data)
    {
        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->save();
        });

        return $status;
    }
    public function logout(User $user)
    {
        $user->tokens()->delete();
        return true;
    }
}
