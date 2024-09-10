<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class About extends Model
{
    use HasFactory;

    // Add the fields that can be mass assigned
    protected $fillable = [
        'online_shop_url',
        'facebook_url',
        'instagram_url',
        'whatsapp_number',
        'description',
    ];
}