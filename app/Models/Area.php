<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Get the users for this area.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the service prices for this area.
     */
    public function servicePrices()
    {
        return $this->hasMany(ServiceAreaPrice::class);
    }

    /**
     * Get the services for this area through pricing.
     */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_area_price')
                    ->withPivot('price')
                    ->withTimestamps();
    }
}
