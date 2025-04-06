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
    public function getTikTokAuthUrl()
    {
        return response()->json($this->SocialConnectRepository->connectTikTok());
    }
    public function connectTikTok()
    {
        return $this->SocialConnectRepository->redirectToTikTok();
    }

    public function handleTikTokCallback(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'state' => 'required|string'
        ]);

        $result = $this->SocialConnectRepository->handleTikTokCallback(
            $request->code,
            $request->state
        );

        if (!$result) {
            return response()->json(['error' => 'Authentication failed'], 400);
        }

        // Here you would typically create/update user in your database
        // and return your own API token (JWT, Sanctum, etc.)

        return response()->json([
            'user' => $result['user_info'],
            'access_token' => $result['tokens']['access_token'],
            'refresh_token' => $result['tokens']['refresh_token']
        ]);
    }

    

    public function getTikTokProfile()
    {
        $user = auth()->user();
        $account = $user->socialAccounts()->where('provider', 'tiktok')->first();
        
        if (!$account) {
            return back()->with('error', 'Please connect your TikTok account first.');
        }
        
        $profile = $this->SocialConnectRepository->getTikTokUserProfile($account->token);
        
        return view('tiktok.profile', compact('profile'));
    }

    public function getTikTokVideos()
    {
        $user = auth()->user();
        $account = $user->socialAccounts()->where('provider', 'tiktok')->first();
        
        if (!$account) {
            return back()->with('error', 'Please connect your TikTok account first.');
        }
        
        $videos = $this->SocialConnectRepository->getTikTokVideos($account->token);
        
        return view('tiktok.videos', compact('videos'));
    }

    public function disconnectTikTok()
    {
        $this->SocialConnectRepository->revokeTikTokAccess(auth()->id());
        
        return back()->with('success', 'TikTok disconnected successfully.');
    }
}
