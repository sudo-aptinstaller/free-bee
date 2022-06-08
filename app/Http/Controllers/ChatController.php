<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Chat;
use App\Models\Message;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function search(Request $request){
        $query = $request->get('q');
        $results = User::where('name','LIKE', '%' . $query . '%')->get();

        return response()->json($results);
    }

    public function singlePersonChat(Request $request){
        $uuid = $request->get('uuid');
        $targetUser = User::where('uuid', $uuid)->first();
        /**
         * Participation Resolver
         */
        $participants = array(auth()->user()->uuid,$targetUser->uuid);
        sort($participants);
        $participation_code = $this->participationCodeGenerator($participants);

        $chat = Chat::where('participation_code', $participation_code)->first();

        if(!$chat){
            $chat = new Chat;
            $admins = array(auth()->user()->uuid);
            sort($admins);
            $chat->participation_code = $participation_code;
            $chat->participants = $participants;
            $chat->admins = $admins;
            $chat->name = $targetUser->name;
            $chat->owner = auth()->user()->id;
            $chat->who = $targetUser->name;
            $chat->save();
        }

        $chat->participant = $this->whoIs($targetUser->uuid);
        
        return response()->json($chat);
    }

    public function whoIs($uuid){
        $user = User::where('uuid', $uuid)->first();
        return $user->name;
    }

    public function sendMessage($pcode, Request $request){
        $chat = Chat::where('participation_code', $pcode)->first();
        $message = new Message;
        $message->name = auth()->user()->name;
        $message->uuid = \Str::uuid();
        $message->chat_id = $chat->id;
        $message->user_id = auth()->user()->id;
        $message->message = $request->get('message');

        $message->save();

        broadcast(new \App\Events\UserSentAMessage($pcode, $message))->toOthers();

        return response()->json($message);
    }

    public function fetchUserChats(Request $request){
        $user = auth()->user();

        $chats = Chat::where('participation_code', 'LIKE', '%'.$user->uuid.'%')->get();

        return response()->json($chats);
    }

    public function fetchChatMessages($pcode){
        $chat = Chat::where('participation_code', $pcode)->with('messages')->first();

        return response()->json($chat);
    }

    public function changeName($pcode, Request $request){
        $chat = Chat::where('participation_code', $pcode)->first();
        $chat->name = $request->get('name');
        $chat->save();

        return response()->json($chat->name);
    }

    
    public function participationCodeGenerator(Array $participants_array){
        $participation_code = '';
        for($x = 0; $x < count($participants_array); $x++) {
            if($x == 0){
                $participation_code = $participants_array[$x];
            }else{
                $participation_code = $participation_code .'-'.$participants_array[$x];
            }
        }
        return $participation_code;
    }
}
