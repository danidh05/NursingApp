<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NurseVisitTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'nurse_visit_id',
        'locale',
        'about',
        'terms_and_conditions',
        'additional_instructions',
        'service_include',
        'description',
        'additional_information',
    ];

    protected $casts = [
        'nurse_visit_id' => 'integer',
    ];

    public function nurseVisit()
    {
        return $this->belongsTo(NurseVisit::class);
    }
}

