<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhysiotherapistTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'physiotherapist_id',
        'locale',
        'name',
        'description',
    ];

    protected $casts = [
        'physiotherapist_id' => 'integer',
    ];

    public function physiotherapist()
    {
        return $this->belongsTo(Physiotherapist::class);
    }
}

