<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

//  PRIVATE CHANNEL — for Laravel Notifications
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

//  PUBLIC CHANNEL — for your wallet-updated event (user.{id})
Broadcast::channel('user.{id}', function ($user, $id) {
    return true; // public, no authentication needed
});
