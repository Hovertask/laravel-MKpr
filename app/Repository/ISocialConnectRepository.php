<?php
namespace App\Repository;

use Laravel\Socialite\Contracts\User as SocialiteUser;

interface ISocialConnectRepository
{
    public function findOrCreateUserByFacebook(SocialiteUser $facebookUser);
    public function getFacebookPosts($accessToken);

    public function fetchFacebookData();
    public function storeFacebookData($data);

    public function storeAccessToken(string $token);
    public function getAccessToken(): ?string;
    public function storeOrUpdatePosts(int $userId, array $posts);

    //tiktok
    public function redirectToTikTok();
    public function handleTikTokCallback();
    public function getTikTokUserProfile($accessToken);
    public function getTikTokVideos($accessToken, $limit = 10);
    public function revokeTikTokAccess($userId);

    //manuak connection
    public function manualConnection(array $request);
}
