<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class CustomVerifyEmail extends BaseVerifyEmail
{
    public function toMail($notifiable)
    {
        $backendSignedURL = $this->verificationUrl($notifiable);

        $frontendURL = config('app.dashboard') . '/verify?' . http_build_query([
            'id' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
            'expires' => Carbon::now()->addMinutes(60)->timestamp,
            'signature' => parse_url($backendSignedURL, PHP_URL_QUERY),
        ]);

        return (new MailMessage)
            ->subject('Verify Your Email')
            ->line('Click the button below to verify your email address.')
            ->action('Verify Email', $frontendURL)
            ->line('If you did not create an account, no further action is required.');
    }

    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
