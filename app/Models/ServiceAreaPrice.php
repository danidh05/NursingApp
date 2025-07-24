<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceAreaPrice extends Model
{
    use HasFactory;

    protected $table = 'service_area_price';

    protected $fillable = [
        'service_id',
        'area_id',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Get the service for this price.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the area for this price.
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}
