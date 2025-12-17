<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RayTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'ray_id',
        'locale',
        'name',
        'about_ray',
        'instructions',
        'additional_information',
    ];

    protected $casts = [
        'ray_id' => 'integer',
    ];

    /**
     * Get the ray that owns the translation.
     */
    public function ray()
    {
        return $this->belongsTo(Ray::class);
    }
}

