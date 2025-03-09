<?php 
namespace App\Repository;

use App\Models\User;
use App\Models\Follow;
use Illuminate\Support\Facades\Auth;

class FollowRepository implements IFollowRepository
{
    public function follow(User $user)
    {
        if(!$this->isFollowing($user)){
            Auth::user()->following()->attach($user->id);
        }
    }

    /**
     * Stop following the given user.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function unfollow(User $user)
    {
        if($this->isFollowing($user)){
            Auth::user()->following()->detach($user->id);
        }
    }

    /**
     * Check if the authenticated user is following the given user.
     *
     * @param User $user
     * @return bool
     */
    public function isFollowing(User $user) : bool
    {
        return Auth::user()->Following()->where('following_id', $user->id)->exists();
    }

    public function getFollowers(User $user)
    {
        return $user->followers()->get();
    }

    /**
     * Get all the users that the given user is following.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFollowing(User $user)
    {
        return $user->following()->get();
    }
}