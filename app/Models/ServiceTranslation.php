<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'locale',
        'name',
    ];

    protected $casts = [
        'service_id' => 'integer',
    ];

    /**
     * Get the service that owns the translation.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
} 