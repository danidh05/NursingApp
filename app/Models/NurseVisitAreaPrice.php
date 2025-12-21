<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NurseVisitAreaPrice extends Model
{
    use HasFactory;

    protected $table = 'nurse_visit_area_price';

    protected $fillable = [
        'nurse_visit_id',
        'area_id',
        'price_per_1_visit',
        'price_per_2_visits',
        'price_per_3_visits',
        'price_per_4_visits',
    ];

    protected $casts = [
        'price_per_1_visit' => 'decimal:2',
        'price_per_2_visits' => 'decimal:2',
        'price_per_3_visits' => 'decimal:2',
        'price_per_4_visits' => 'decimal:2',
    ];

    public function nurseVisit()
    {
        return $this->belongsTo(NurseVisit::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}

