<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'nurse_id',
        'user_id',
        'rating',
        'comment',
    ];

    public function nurse()
    {
        return $this->belongsTo(Nurse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}