<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'password',
        'role_id',
        'email_verified_at',               // Include this field to allow mass assignment
        'location',
        'latitude',
        'longitude',
        'confirmation_code',
        'confirmation_code_expires_at',
        'is_first_login', 
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'confirmation_code',               // Optionally hide the confirmation code
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_first_login' => 'boolean',     // Casting is_first_login as a boolean
        'confirmation_code_expires_at' => 'datetime', // Ensure this timestamp is casted
    ];

    /**
     * Get the role that the user belongs to.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the requests made by the user.
     */
    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    /**
     * Get the notifications for the user.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}