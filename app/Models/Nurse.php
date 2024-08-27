<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nurse extends Model
{
    use HasFactory;
     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone_number',
        'address',
        'profile_picture',
    ];

    /**
     * Get the requests for the nurse.
     */
    public function requests()
    {
        return $this->hasMany(Request::class);
    }
}