<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DutyTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'duty_id',
        'locale',
        'about',
        'terms_and_conditions',
        'additional_instructions',
        'service_include',
        'description',
        'additional_information',
    ];

    protected $casts = [
        'duty_id' => 'integer',
    ];

    public function duty()
    {
        return $this->belongsTo(Duty::class);
    }
}

