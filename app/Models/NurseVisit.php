<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class NurseVisit extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'image',
        'price_per_1_visit',
        'price_per_2_visits',
        'price_per_3_visits',
        'price_per_4_visits',
    ];

    protected $casts = [
        'price_per_1_visit' => 'decimal:2',
        'price_per_2_visits' => 'decimal:2',
        'price_per_3_visits' => 'decimal:2',
        'price_per_4_visits' => 'decimal:2',
    ];

    /**
     * Override the translation foreign key to use nurse_visit_id instead of nursevisit_id.
     */
    protected function getTranslationForeignKey(): string
    {
        return 'nurse_visit_id';
    }

    /**
     * Get the requests that use this nurse visit.
     */
    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    /**
     * Get the area prices for this nurse visit.
     */
    public function areaPrices()
    {
        return $this->hasMany(NurseVisitAreaPrice::class);
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
     * Get price for a specific number of visits per day.
     */
    public function getPriceForVisits(int $visitsPerDay, ?int $areaId = null): float
    {
        if ($areaId) {
            $areaPrice = $this->areaPrices()->where('area_id', $areaId)->first();
            if ($areaPrice) {
                return match($visitsPerDay) {
                    1 => $areaPrice->price_per_1_visit,
                    2 => $areaPrice->price_per_2_visits,
                    3 => $areaPrice->price_per_3_visits,
                    4 => $areaPrice->price_per_4_visits,
                    default => $this->price_per_1_visit,
                };
            }
        }

        // Fallback to base prices
        return match($visitsPerDay) {
            1 => $this->price_per_1_visit,
            2 => $this->price_per_2_visits,
            3 => $this->price_per_3_visits,
            4 => $this->price_per_4_visits,
            default => $this->price_per_1_visit,
        };
    }
}

