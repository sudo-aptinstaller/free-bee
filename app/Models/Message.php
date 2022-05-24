<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    public function chat(){
        return $this->hasOne(Chat::class, 'id', 'chat_id');
    }

    public function user(){
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
