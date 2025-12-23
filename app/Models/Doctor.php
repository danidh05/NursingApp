<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Doctor extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'doctor_category_id',
        'area_id',
        'name',
        'specification',
        'years_of_experience',
        'image',
        'price',
        'job_name',
        'description',
        'additional_information',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'years_of_experience' => 'integer',
    ];

    public array $translatable = [
        'specification',
        'job_name',
        'description',
        'additional_information',
    ];

    public function doctorCategory()
    {
        return $this->belongsTo(DoctorCategory::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function areaPrices()
    {
        return $this->hasMany(DoctorAreaPrice::class);
    }

    public function availabilities()
    {
        return $this->hasMany(DoctorAvailability::class);
    }

    public function operations()
    {
        return $this->hasMany(DoctorOperation::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}

