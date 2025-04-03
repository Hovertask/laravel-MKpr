<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Auth\EmailVerificationRequest as BaseEmailVerificationRequest;

class ApiEmailVerificationRequest extends BaseEmailVerificationRequest
{
    public function authorize()
    {
        if (! $this->hasValidSignature()) {
            return false;
        }

        // For API, we'll get the user from the route parameter or query string
        $user = $this->route('id') 
            ? $this->userModel()->findOrFail($this->route('id'))
            : $this->userModel()->where('email', $this->query('email'))->first();

        if (! hash_equals(
            (string) $this->route('hash'),
            sha1($user->getEmailForVerification())
        )) {
            return false;
        }

        return true;
    }
}