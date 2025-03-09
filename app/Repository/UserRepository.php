<?php
namespace App\Repository;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class UserRepository implements IUserRepository
{
    public function create(array $data): User
    {
        return User::create([
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
            'referal_username' => $data['referal_username'] ?? null,
            'referal_code' => $data['referal_code'] ?? null,
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
