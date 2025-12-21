<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BabysitterTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'babysitter_id',
        'locale',
        'about',
        'terms_and_conditions',
        'additional_instructions',
        'service_include',
        'description',
        'additional_information',
    ];

    protected $casts = [
        'babysitter_id' => 'integer',
    ];

    public function babysitter()
    {
        return $this->belongsTo(Babysitter::class);
    }
}

