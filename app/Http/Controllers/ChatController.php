<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Http\Requests\StoreChatRequest;
use App\Http\Requests\UpdateChatRequest;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller {

    public function createNewChat(Request $request, $targetUserName) {
        $user = User::where('access_token', $request->header('access_token'))->first();
        $targetUser = User::where('userName', $targetUserName)->first();
        $openedChat = Chat::where('user1_id', $user->id)->where('user2_id', $targetUser->id)->first();
        if ($openedChat == null) {
            $openedChat = Chat::where('user2_id', $user->id)->where('user1_id', $targetUser->id)->first();
            if ($openedChat == null) {
                $newChat = new Chat;
                $newChat->user1_id = $user->id;
                $newChat->user2_id = $targetUser->id;
                $newChat->save();
                return response([
                    "status" => "created",
                    "chatId" => $newChat->id,
                    "createdBy" => $user->userName
                ]);
            } else {
                return response([
                    "status" => "alreadyExisted"
                ]);
            }
        } else {
            return response([
                "status" => "alreadyExisted"
            ]);
        }
    }
    public function getChatInfo(Request $request, $targetUserName) {
        $user = User::where('access_token', $request->header('access_token'))->first();
        $targetUser = User::where('userName', $targetUserName)->first()->makeHidden('password', 'email', 'access_token', 'created_at', 'updated_at');
        if ($targetUser->profileImage != 'default') {
            $targetUser->profileImage = asset('storage/img/users/' . $targetUser->profileImage);
        }
        $chat = Chat::where('user1_id', $user->id)->where('user2_id', $targetUser->id)->first();
        if ($chat == null) {
            $chat = Chat::where('user2_id', $user->id)->where('user1_id', $targetUser->id)->first();
            if ($chat == null) {
                return response('chatNotFound', 404);
            }
        }
        return response([
            "currentUserName" => $user->userName,
            "targetUser" => $targetUser,
            "chatId" => $chat->id
        ]);
    }

    public function getOpenedChats(Request $request) {
        $user = User::where('access_token', $request->header('access_token'))->first();
        $openedChats = [];
        $user1chats = Chat::where('user1_id', $user->id)->get();
        foreach ($user1chats as $user1chat) {
            $targetUser2 = User::find($user1chat->user2_id)->makeHidden('password', 'email', 'access_token', 'created_at', 'updated_at');
            if ($targetUser2->profileImage != "default") {
                $targetUser2->profileImage = asset('storage/img/users/' . $targetUser2->profileImage);
            }
            $targetUser2->chatId = $user1chat->id;
            array_push($openedChats, $targetUser2);
        }
        $user2chats = Chat::where('user2_id', $user->id)->get();
        foreach ($user2chats as $user2chat) {
            $targetUser1 = User::find($user2chat->user1_id)->makeHidden('password', 'email', 'access_token', 'created_at', 'updated_at');
            if ($targetUser1->profileImage != "default") {
                $targetUser1->profileImage = asset('storage/img/users/' . $targetUser1->profileImage);
            }
            $targetUser1->chatId = $user2chat->id;
            array_push($openedChats, $targetUser1);
        }

        return response($openedChats);
    }
}
