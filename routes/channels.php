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

    Broadcast::channel('chat-{uuid}', function($user, $uuid){
        if(strpos($uuid, $user->uuid) !== false){
            return $user;
        }
    },['guards' => ['web']]);

    Broadcast::channel('mymo-presence', function($user){
        return $user;
    },['guards' => ['web']]);