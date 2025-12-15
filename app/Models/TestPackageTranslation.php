<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestPackageTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_package_id',
        'locale',
        'name',
        'about_test',
        'instructions',
    ];

    protected $casts = [
        'test_package_id' => 'integer',
    ];

    /**
     * Get the test package that owns the translation.
     */
    public function testPackage()
    {
        return $this->belongsTo(TestPackage::class, 'test_package_id');
    }
}
