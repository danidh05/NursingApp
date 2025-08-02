<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'locale',
        'name',
    ];

    protected $casts = [
        'request_id' => 'integer',
    ];

    /**
     * Get the request that owns the translation.
     */
    public function request()
    {
        return $this->belongsTo(Request::class);
    }
} 