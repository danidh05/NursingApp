<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorCategoryTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_category_id',
        'locale',
        'name',
    ];

    public function doctorCategory()
    {
        return $this->belongsTo(DoctorCategory::class);
    }
}

