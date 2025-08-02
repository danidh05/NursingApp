<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NurseTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'nurse_id',
        'locale',
        'name',
    ];

    protected $casts = [
        'nurse_id' => 'integer',
    ];

    /**
     * Get the nurse that owns the translation.
     */
    public function nurse()
    {
        return $this->belongsTo(Nurse::class);
    }
} 