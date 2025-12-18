<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Machine extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'price',
        'image',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Get the requests that use this machine.
     */
    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    /**
     * Get the area prices for this machine.
     */
    public function areaPrices()
    {
        return $this->hasMany(MachineAreaPrice::class);
    }

    /**
     * Get the areas for this machine through pricing.
     */
    public function areas()
    {
        return $this->belongsToMany(Area::class, 'machine_area_price')
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

