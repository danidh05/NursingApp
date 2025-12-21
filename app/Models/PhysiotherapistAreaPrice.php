<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhysiotherapistAreaPrice extends Model
{
    use HasFactory;

    protected $table = 'physiotherapist_area_price';

    protected $fillable = [
        'physiotherapist_id',
        'area_id',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Get the physiotherapist that owns this area price.
     */
    public function physiotherapist()
    {
        return $this->belongsTo(Physiotherapist::class);
    }

    /**
     * Get the area for this price.
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}

