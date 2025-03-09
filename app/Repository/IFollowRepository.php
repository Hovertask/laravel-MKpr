<?php
namespace App\Repository;

use App\Models\User;
use App\Models\Follow;

interface IFollowRepository
{
    public function follow(User $user);
    public function unfollow(User $user);
    public function isFollowing(User $user) : bool;
    public function getFollowers(User $user);
    public function getFollowing(User $user);
}