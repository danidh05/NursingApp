<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class About extends Model
{
    use HasFactory;

    protected $fillable = [
        'online_shop_url',
        'facebook_url',
        'instagram_url',
        'whatsapp_numbers', // Store multiple numbers as JSON
        'description',
        'tiktok_url', // New field
    ];

    protected $casts = [
        'whatsapp_numbers' => 'array', // Automatically cast JSON to array
    ];
}