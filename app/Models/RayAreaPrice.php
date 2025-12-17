<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RayAreaPrice extends Model
{
    use HasFactory;

    protected $table = 'ray_area_price';

    protected $fillable = [
        'ray_id',
        'area_id',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Get the ray that owns this area price.
     */
    public function ray()
    {
        return $this->belongsTo(Ray::class);
    }

    /**
     * Get the area for this price.
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}

