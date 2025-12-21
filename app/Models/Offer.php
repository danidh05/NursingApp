<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Offer extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'offer_price',
        'old_price',
        'offer_available_until',
        'category_id',
        'image',
    ];

    protected $casts = [
        'offer_price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'category_id' => 'integer',
    ];

    /**
     * Get the category that this offer belongs to (optional).
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the area prices for this offer.
     */
    public function areaPrices()
    {
        return $this->hasMany(OfferAreaPrice::class);
    }

    /**
     * Get the areas for this offer through pricing.
     */
    public function areas()
    {
        return $this->belongsToMany(Area::class, 'offer_area_price')
                    ->withPivot('offer_price', 'old_price')
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

