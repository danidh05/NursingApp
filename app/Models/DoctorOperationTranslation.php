<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorOperationTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_operation_id',
        'locale',
        'name',
        'description',
        'additional_information',
    ];

    public function doctorOperation()
    {
        return $this->belongsTo(DoctorOperation::class);
    }
}

