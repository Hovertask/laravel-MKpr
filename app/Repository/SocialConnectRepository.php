<?php
namespace App\Repository;

use Facebook\Facebook;
use App\Models\FacebookUser;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use App\Repository\ISocialConnectRepository;
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
}