<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestPackage extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'results',
        'price',
        'image',
        'show_details',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'show_details' => 'boolean',
    ];

    /**
     * Get the tests included in this package.
     */
    public function tests()
    {
        return $this->belongsToMany(Test::class, 'test_package_tests', 'test_package_id', 'test_id')
                    ->withTimestamps();
    }

    /**
     * Get the image URL (full URL for frontend).
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->image);
    }

    /**
     * Override the translation foreign key to use test_package_id instead of testpackage_id.
     */
    protected function getTranslationForeignKey(): string
    {
        return 'test_package_id';
    }
}
