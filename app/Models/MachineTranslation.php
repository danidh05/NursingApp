<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'locale',
        'name',
        'description',
        'additional_information',
    ];

    protected $casts = [
        'machine_id' => 'integer',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}

