<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Repository\SocialConnectRepository;

class SocialConnectController extends Controller
{
    protected $SocialConnectRepository;


    public function __construct(SocialConnectRepository $SocialConnectRepository)
    {
        $this->SocialConnectRepository = $SocialConnectRepository;
    }
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->stateless()->redirect();
    }

    public function handleFacebookCallback()
    {
        $facebookUser = Socialite::driver('facebook')->stateless()->user();

        // Find or create the user in your database
        $user = $this->SocialConnectRepository->findOrCreateUserByFacebook($facebookUser);
        // Redirect to the desired page
        return redirect('/dashboard');
    }

    public function getFacebookData()
    {
        $data = $this->SocialConnectRepository->fetchFacebookData();
        $user = $this->SocialConnectRepository->storeFacebookData($data);

        return response()->json(['message' => 'Facebook Data Stored Successfully!', 'user' => $user]);
    }
}
