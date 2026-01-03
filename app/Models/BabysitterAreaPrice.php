<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BabysitterAreaPrice extends Model
{
    use HasFactory;

    protected $table = 'babysitter_area_price';

    protected $fillable = [
        'babysitter_id',
        'area_id',
        'day_shift_price_12_hours',
        'day_shift_price_24_hours', // Deprecated: kept for backward compatibility
        'night_shift_price_12_hours',
        'night_shift_price_24_hours', // Deprecated: kept for backward compatibility
        'price_24_hours', // Separate 24-hour pricing (not day/night specific) - USE THIS
    ];

    protected $casts = [
        'day_shift_price_12_hours' => 'decimal:2',
        'day_shift_price_24_hours' => 'decimal:2', // Deprecated
        'night_shift_price_12_hours' => 'decimal:2',
        'night_shift_price_24_hours' => 'decimal:2', // Deprecated
        'price_24_hours' => 'decimal:2',
    ];

    public function babysitter()
    {
        return $this->belongsTo(Babysitter::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}

