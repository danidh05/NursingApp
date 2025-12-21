<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Babysitter extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'image',
        'day_shift_price_12_hours',
        'day_shift_price_24_hours',
        'night_shift_price_12_hours',
        'night_shift_price_24_hours',
    ];

    protected $casts = [
        'day_shift_price_12_hours' => 'decimal:2',
        'day_shift_price_24_hours' => 'decimal:2',
        'night_shift_price_12_hours' => 'decimal:2',
        'night_shift_price_24_hours' => 'decimal:2',
    ];

    /**
     * Override the translation foreign key to use babysitter_id (already correct, but explicit).
     */
    protected function getTranslationForeignKey(): string
    {
        return 'babysitter_id';
    }

    /**
     * Get the requests that use this babysitter.
     */
    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    /**
     * Get the area prices for this babysitter.
     */
    public function areaPrices()
    {
        return $this->hasMany(BabysitterAreaPrice::class);
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

    /**
     * Get price for a specific duration and shift.
     */
    public function getPriceForDuration(int $durationHours, bool $isDayShift, ?int $areaId = null): float
    {
        if ($areaId) {
            $areaPrice = $this->areaPrices()->where('area_id', $areaId)->first();
            if ($areaPrice) {
                $priceField = ($isDayShift ? 'day' : 'night') . '_shift_price_' . $durationHours . '_hours';
                return $areaPrice->$priceField ?? 0;
            }
        }

        // Fallback to base prices
        $priceField = ($isDayShift ? 'day' : 'night') . '_shift_price_' . $durationHours . '_hours';
        return $this->$priceField ?? 0;
    }
}

