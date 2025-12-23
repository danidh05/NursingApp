<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorOperationAreaPrice extends Model
{
    use HasFactory;

    protected $table = 'doctor_operation_area_price';

    protected $fillable = [
        'doctor_operation_id',
        'area_id',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function operation()
    {
        return $this->belongsTo(DoctorOperation::class, 'doctor_operation_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}

