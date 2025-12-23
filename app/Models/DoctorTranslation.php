<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'locale',
        'specification',
        'job_name',
        'description',
        'additional_information',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}

