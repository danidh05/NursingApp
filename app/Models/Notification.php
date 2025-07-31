<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'sent_by_admin_id',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Get the user that the notification belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who sent this notification (for custom notifications).
     */
    public function sentByAdmin()
    {
        return $this->belongsTo(User::class, 'sent_by_admin_id');
    }

    /**
     * Check if this is a custom notification sent by an admin.
     */
    public function isCustomNotification(): bool
    {
        return $this->type === 'custom' && !is_null($this->sent_by_admin_id);
    }
}