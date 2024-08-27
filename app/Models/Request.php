<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;
     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'nurse_id',
        'service_id',
        'status',
        'scheduled_time',
        'location',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_time' => 'datetime',
    ];

    /**
     * Get the user that made the request.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the nurse assigned to the request.
     */
    public function nurse()
    {
        return $this->belongsTo(Nurse::class);
    }

    /**
     * Get the service associated with the request.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}