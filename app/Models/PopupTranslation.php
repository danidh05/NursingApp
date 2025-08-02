<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PopupTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'popup_id',
        'locale',
        'title',
        'content',
    ];

    protected $casts = [
        'popup_id' => 'integer',
    ];

    /**
     * Get the popup that owns the translation.
     */
    public function popup()
    {
        return $this->belongsTo(Popup::class);
    }
} 