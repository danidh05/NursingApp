<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'sender_id',
        'type',
        'text',
        'latitude',
        'longitude',
        'media_path',
    ];

    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
        // 'type' => ChatMessageType::class, // optional enum later
    ];
    protected $touches = ['thread']; // keep thread updated_at fresh
    
    public function thread()
    {
        return $this->belongsTo(ChatThread::class, 'thread_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}