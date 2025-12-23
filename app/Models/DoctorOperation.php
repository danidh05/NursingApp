<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DoctorOperation extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'doctor_id',
        'image',
        'price',
        'name',
        'description',
        'additional_information',
        'building_name',
        'location_description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public array $translatable = [
        'name',
        'description',
        'additional_information',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function translations()
    {
        return $this->hasMany(DoctorOperationTranslation::class);
    }

    public function areaPrices()
    {
        return $this->hasMany(DoctorOperationAreaPrice::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}

