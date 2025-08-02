<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SliderTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'slider_id',
        'locale',
        'title',
        'subtitle',
    ];

    protected $casts = [
        'slider_id' => 'integer',
    ];

    /**
     * Get the slider that owns the translation.
     */
    public function slider()
    {
        return $this->belongsTo(Slider::class);
    }
} 