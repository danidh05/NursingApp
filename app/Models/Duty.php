<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Duty extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'image',
        'day_shift_price_4_hours',
        'day_shift_price_6_hours',
        'day_shift_price_8_hours',
        'day_shift_price_12_hours',
        'night_shift_price_4_hours',
        'night_shift_price_6_hours',
        'night_shift_price_8_hours',
        'night_shift_price_12_hours',
        'continuous_care_price',
    ];

    protected $casts = [
        'day_shift_price_4_hours' => 'decimal:2',
        'day_shift_price_6_hours' => 'decimal:2',
        'day_shift_price_8_hours' => 'decimal:2',
        'day_shift_price_12_hours' => 'decimal:2',
        'night_shift_price_4_hours' => 'decimal:2',
        'night_shift_price_6_hours' => 'decimal:2',
        'night_shift_price_8_hours' => 'decimal:2',
        'night_shift_price_12_hours' => 'decimal:2',
        'continuous_care_price' => 'decimal:2',
    ];

    /**
     * Get the requests that use this duty.
     */
    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    /**
     * Get the area prices for this duty.
     */
    public function areaPrices()
    {
        return $this->hasMany(DutyAreaPrice::class);
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
                return $areaPrice->$priceField ?? $this->continuous_care_price;
            }
        }

        // Fallback to base prices
        $priceField = ($isDayShift ? 'day' : 'night') . '_shift_price_' . $durationHours . '_hours';
        return $this->$priceField ?? $this->continuous_care_price;
    }

    /**
     * Get continuous care price.
     */
    public function getContinuousCarePrice(?int $areaId = null): float
    {
        if ($areaId) {
            $areaPrice = $this->areaPrices()->where('area_id', $areaId)->first();
            if ($areaPrice) {
                return $areaPrice->continuous_care_price;
            }
        }

        return $this->continuous_care_price;
    }
}

