<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DutyAreaPrice extends Model
{
    use HasFactory;

    protected $table = 'duty_area_price';

    protected $fillable = [
        'duty_id',
        'area_id',
        'day_shift_price_4_hours',
        'day_shift_price_6_hours',
        'day_shift_price_8_hours',
        'day_shift_price_12_hours',
        'night_shift_price_4_hours',
        'night_shift_price_6_hours',
        'night_shift_price_8_hours',
        'night_shift_price_12_hours',
        'price_24_hours', // Separate 24-hour pricing (not day/night specific)
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
        'price_24_hours' => 'decimal:2',
        'continuous_care_price' => 'decimal:2',
    ];

    public function duty()
    {
        return $this->belongsTo(Duty::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}

