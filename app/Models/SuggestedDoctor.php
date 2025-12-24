<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuggestedDoctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Get the doctor that is suggested.
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
