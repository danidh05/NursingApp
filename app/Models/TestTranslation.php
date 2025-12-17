<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'locale',
        'name',
        'about_test',
        'instructions',
    ];

    protected $casts = [
        'test_id' => 'integer',
    ];

    /**
     * Get the test that owns the translation.
     */
    public function test()
    {
        return $this->belongsTo(Test::class);
    }
}
