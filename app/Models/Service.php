<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory, HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description', // Non-translatable (legacy), but translations also have description
        'price',
        'discount_price',
        'category_id',
        'image', // Laravel Storage path
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
    ];

    /**
     * Get the category that the service belongs to.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Define the many-to-many relationship with requests.
     */
    public function requests()
    {
        return $this->belongsToMany(Request::class, 'request_services', 'service_id', 'request_id');
    }

    /**
     * Get the area prices for this service.
     */
    public function areaPrices()
    {
        return $this->hasMany(ServiceAreaPrice::class);
    }

    /**
     * Get the areas for this service through pricing.
     */
    public function areas()
    {
        return $this->belongsToMany(Area::class, 'service_area_price')
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

        // Return full URL using Laravel Storage
        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->image);
    }
}