<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DoctorCategory extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['image'];

    public array $translatable = ['name'];

    public function translations()
    {
        return $this->hasMany(DoctorCategoryTranslation::class);
    }

    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}

