<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MostRequestedService extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Get the service that is most requested.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
