<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferAreaPrice extends Model
{
    use HasFactory;

    protected $table = 'offer_area_price';

    protected $fillable = [
        'offer_id',
        'area_id',
        'offer_price',
        'old_price',
    ];

    protected $casts = [
        'offer_price' => 'decimal:2',
        'old_price' => 'decimal:2',
    ];

    /**
     * Get the offer that owns this area price.
     */
    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * Get the area for this price.
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}

