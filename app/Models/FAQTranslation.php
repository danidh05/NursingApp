<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FAQTranslation extends Model
{
    use HasFactory;
    
    protected $table = 'faq_translations';

    protected $fillable = [
        'faq_id',
        'locale',
        'question',
        'answer',
    ];

    protected $casts = [
        'faq_id' => 'integer',
    ];

    /**
     * Get the FAQ that owns the translation.
     */
    public function faq()
    {
        return $this->belongsTo(FAQ::class);
    }
} 