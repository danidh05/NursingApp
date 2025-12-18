<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineAreaPrice extends Model
{
    use HasFactory;

    protected $table = 'machine_area_price';

    protected $fillable = [
        'machine_id',
        'area_id',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Get the machine that owns this area price.
     */
    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * Get the area for this price.
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}

