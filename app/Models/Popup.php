<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Popup extends Model
{
    use HasFactory;

    // Add constants for popup types
    public const TYPE_INFO = 'info';
    public const TYPE_WARNING = 'warning';
    public const TYPE_PROMO = 'promo';
    public const TYPE_BIRTHDAY = 'birthday';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'image',
        'title',
        'content',
        'type',
        'start_date',
        'end_date',
        'is_active',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user this popup is for (if any).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only active popups for a specific user or global ones.
     */
        public function scopeActiveForUser($query, ?User $user = null)
    {
        $now = now();
        
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>', $now);
            })
            ->where(function ($q) use ($user) {
                $q->whereNull('user_id') // Global popups
                  ->when($user, function ($q) use ($user) {
                      $q->orWhere('user_id', $user->id); // User-specific popups
                  });
            });
    }

    /**
     * Check if the popup is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        // Check start date
        if ($this->start_date && $this->start_date->gt($now)) {
            return false;
        }

        // Check end date (popup is inactive if current time is past end date)
        if ($this->end_date && $this->end_date->lte($now)) {
            return false;
        }

        return true;
    }
}