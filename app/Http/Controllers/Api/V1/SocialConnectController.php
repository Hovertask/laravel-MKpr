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

    //TikTok
    public function connectTikTok()
    {
        return $this->facebookRepository->redirectToTikTok();
    }

    public function tiktokCallback()
    {
        $success = $this->facebookRepository->handleTikTokCallback();
        
        return redirect()->route('profile')
            ->with($success ? 'success' : 'error', 
                $success ? 'TikTok connected successfully!' : 'Failed to connect TikTok.');
    }

    public function getTikTokProfile()
    {
        $user = auth()->user();
        $account = $user->socialAccounts()->where('provider', 'tiktok')->first();
        
        if (!$account) {
            return back()->with('error', 'Please connect your TikTok account first.');
        }
        
        $profile = $this->facebookRepository->getTikTokUserProfile($account->token);
        
        return view('tiktok.profile', compact('profile'));
    }

    public function getTikTokVideos()
    {
        $user = auth()->user();
        $account = $user->socialAccounts()->where('provider', 'tiktok')->first();
        
        if (!$account) {
            return back()->with('error', 'Please connect your TikTok account first.');
        }
        
        $videos = $this->facebookRepository->getTikTokVideos($account->token);
        
        return view('tiktok.videos', compact('videos'));
    }

    public function disconnectTikTok()
    {
        $this->facebookRepository->revokeTikTokAccess(auth()->id());
        
        return back()->with('success', 'TikTok disconnected successfully.');
    }
}
