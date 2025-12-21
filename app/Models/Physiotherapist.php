<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Physiotherapist extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'image',
        'job_name',
        'job_specification',
        'specialization',
        'years_of_experience',
        'price',
    ];

    protected $casts = [
        'years_of_experience' => 'integer',
        'price' => 'decimal:2',
    ];

    /**
     * Get the requests that use this physiotherapist.
     */
    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    /**
     * Get the area prices for this physiotherapist.
     */
    public function areaPrices()
    {
        return $this->hasMany(PhysiotherapistAreaPrice::class);
    }

    /**
     * Get the areas for this physiotherapist through pricing.
     */
    public function areas()
    {
        return $this->belongsToMany(Area::class, 'physiotherapist_area_price')
                    ->withPivot('price')
                    ->withTimestamps();
    }

    /**
     * Get the image URL (full URL for frontend).
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        return Storage::disk('public')->url($this->image);
    }
}

