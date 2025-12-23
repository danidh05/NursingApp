<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorAreaPrice extends Model
{
    use HasFactory;

    protected $table = 'doctor_area_price';

    protected $fillable = [
        'doctor_id',
        'area_id',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}

