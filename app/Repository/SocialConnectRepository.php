<?php
namespace App\Repository;

use Facebook\Facebook;
use App\Models\FacebookUser;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use App\Models\ManualSocialAccountLinking;
use App\Repository\ISocialConnectRepository;
use Laravel\Socialite\Contracts\User as SocialiteUser;
//use App\Models\SocialConnect;

class SocialConnectRepository implements ISocialConnectRepository
{
    protected $facebookToken;
    
    public function __construct()
    {
        $this->facebookToken = session('facebook_access_token');
    }

    public function findOrCreateUserByFacebook(SocialiteUser $facebookUser)
    {
        $user = FacebookUser::where('facebook_id', $facebookUser->getId())->first();

        if (!$user) {
            $user = FacebookUser::create([
                'name' => $facebookUser->getName(),
                'email' => $facebookUser->getEmail(),
                'facebook_id' => $facebookUser->getId(),
                'avatar' => $facebookUser->getAvatar(),
            ]);
        }

        return $user;
    }

    public function getFacebookPosts($accessToken)
    {
        $fb = new Facebook([
            'app_id' => env('FACEBOOK_CLIENT_ID'),
            'app_secret' => env('FACEBOOK_CLIENT_SECRET'),
            'default_graph_version' => 'v12.0',
        ]);

        try {
            $response = $fb->get('/me/posts', $accessToken);
            $posts = $response->getGraphEdge();
            return $posts;
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            // Handle error
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            // Handle error
        }
    }

    public function fetchFacebookData()
    {
        if(!$this->facebookToken) {
            return false;
        }
        
        //$response = Http::get('https://graph.facebook.com/me?fields=id,name,email&access_token='.$this->facebookToken);
        $response = Http::get("https://graph.facebook.com/me?fields=id,name,followers_count,posts.limit(5){message,created_time}&access_token={$this->facebookToken}");

        return $response->json();
    }

    public function storeFacebookData($data)
    {
       $user = FacebookUser::UpdateOrCreate(['facebook_id' => $data['facebook_id']], 
       [
           'name' => $data['name'],
           'followers_count' => $data['followers_count'] ?? 0,
           'posts_count' => count($data['posts']['data'] ?? [])
       ]
       );

       // Store Posts
        if (isset($data['posts']['data'])) {
            foreach ($data['posts']['data'] as $post) {
                FacebookPost::updateOrCreate(
                    [
                        'facebook_user_id' => $user->id,
                        'message' => $post['message'] ?? 'No message',
                        'created_time' => $post['created_time'],
                    ]
                );
            }
        }
    }

    public function storeAccessToken(string $token)
    {
        session(['facebook_access_token' => $token]);
    }

    public function getAccessToken(): ?string
    {
        return session('facebook_access_token');
    }

    public function storeOrUpdatePosts(int $userId, array $posts)
    {
        // foreach ($posts as $post) {
        //     FacebookPost::updateOrCreate(
        //         [
        //             'facebook_user_id' => $userId,
        //             'message' => $post['message'] ?? 'No message',
        //             'created_time' => $post['created_time'],
        //         ]
        //     );
    }

    //TIKTOK Starts here

    public function connectTikTok()
    {
        $state = bin2hex(random_bytes(16));
        
        return [
            'url' => 'https://www.tiktok.com/auth/authorize/?' . http_build_query([
                'client_key' => config('services.tiktok.client_id'),
                'redirect_uri' => config('services.tiktok.redirect'),
                'response_type' => 'code',
                'scope' => 'user.info.basic,video.list',
                'state' => $state,
            ]),
            'state' => $state
        ];
    }

    public function redirectToTikTok()
    {
        return Socialite::driver('tiktok')
            ->scopes(['user.info.basic', 'video.list'])
            ->redirect();
    }

    public function handleTikTokCallback(string $code)
    {
        try {
            // Exchange code for tokens
            $response = Http::asForm()->post('https://open.tiktokapis.com/v2/oauth/token/', [
                'client_key' => config('services.tiktok.client_id'),
                'client_secret' => config('services.tiktok.client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('services.tiktok.redirect'),
            ]);
    
            $tokens = $response->json();
            
            if (!isset($tokens['access_token'])) {
                throw new \Exception('Failed to get access token');
            }
    
            // Get user info
            $userInfo = Http::withHeaders([
                'Authorization' => 'Bearer ' . $tokens['access_token']
            ])->get('https://open.tiktokapis.com/v2/user/info/', [
                'fields' => 'open_id,union_id,avatar_url,display_name'
            ])->json();
    
            $user = auth()->user(); // Or create new user if needed
            
            $socialAccount = SocialAccount::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => 'tiktok'
                ],
                [
                    'provider_user_id' => $userInfo['data']['open_id'],
                    'token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'expires_at' => now()->addSeconds($tokens['expires_in']),
                    'username' => $userInfo['data']['display_name'] ?? null,
                    'avatar' => $userInfo['data']['avatar_url'] ?? null
                ]
            );
    
            return [
                'tokens' => $tokens,
                'user_info' => $userInfo,
                'social_account' => $socialAccount
            ];
            
        } catch (\Exception $e) {
            Log::error('TikTok API error: ' . $e->getMessage());
            return null;
        }
    }

    public function getTikTokUserProfile($accessToken)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get('https://open.tiktokapis.com/v2/user/info/', [
                'fields' => 'open_id,union_id,avatar_url,display_name,bio_description',
            ]);
            
            return $response->json();
        } catch (\Exception $e) {
            Log::error('TikTok profile API error: ' . $e->getMessage());
            return null;
        }
    }

    public function getTikTokVideos($accessToken, $limit = 10)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get('https://open.tiktokapis.com/v2/video/list/', [
                'max_count' => $limit,
                'fields' => 'id,title,cover_image_url,embed_html,embed_link,like_count,comment_count,share_count',
            ]);
            
            return $response->json();
        } catch (\Exception $e) {
            Log::error('TikTok videos API error: ' . $e->getMessage());
            return null;
        }
    }

    public function revokeTikTokAccess($userId)
    {
        return SocialAccount::where('user_id', $userId)
            ->where('provider', 'tiktok')
            ->delete();
    }

    public function refreshTikTokToken($refreshToken)
    {
        try {
            $response = Http::post('https://open.tiktokapis.com/v2/oauth/token/', [
                'client_key' => config('services.tiktok.client_id'),
                'client_secret' => config('services.tiktok.client_secret'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);
            
            return $response->json();
        } catch (\Exception $e) {
            Log::error('TikTok token refresh error: ' . $e->getMessage());
            return null;
        }
    }

    //manuak connection
    public function manualConnection(array $request)
    {
        $user = auth()->user();

        $socials = ManualSocialAccountLinking::updateOrCreate(
            ['user_id' => $user->id],
            [
                'facebook_profile'   => $request['facebook_profile'],
                'facebook_uname'     => $request['facebook_uname'],
                'tiktok_profile'     => $request['tiktok_profile'],
                'tiktok_uname'       => $request['tiktok_uname'],
                'instagram_profile'  => $request['instagram_profile'],
                'instagram_uname'    => $request['instagram_uname'],
                'x_profile'          => $request['x_profile'],
                'x_uname'            => $request['x_uname'],
            ]
        );

        return $socials;
    }
}