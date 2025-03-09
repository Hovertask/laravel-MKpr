<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\FollowRepository;

class FollowController extends Controller
{
    protected $followRepository;


    public function __construct(FollowRepository $followRepository)
    {
        $this->followRepository = $followRepository;
    }

/**
 * Follow the given user.
 *
 * @param \App\Models\User $user
 * @return \Illuminate\Http\JsonResponse
 */

/**
 * Follow the given user.
 *
 * @param \App\Models\User $user
 * @return \Illuminate\Http\JsonResponse
 */

    public function follow(User $user)
    {
        $this->followRepository->follow($user);

        return response()->json(['message' => 'You are now following.', $user->name]);
    }

    public function unfollow(User $user)
    {
        $this->followRepository->unfollow($user);
        return response()->json(['message' => 'You have unfollowed.', $user->name]);

    }
}
