<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Request extends Model
{
    use SoftDeletes;
    use HasFactory;

    // Define status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELED = 'canceled';

    // Define time type constants
    public const TIME_TYPE_FULL = 'full-time';
    public const TIME_TYPE_PART = 'part-time';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'nurse_id',
        'status',
        'scheduled_time',
        'ending_time',
        'location',
        'time_type',
        'problem_description',
        'nurse_gender',
        'full_name',
        'phone_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_time' => 'datetime',
        'ending_time' => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    /**
     * Get all valid status values.
     *
     * @return array
     */
    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELED,
        ];
    }

    /**
     * Get all valid time types.
     *
     * @return array
     */
    public static function getValidTimeTypes(): array
    {
        return [
            self::TIME_TYPE_FULL,
            self::TIME_TYPE_PART,
        ];
    }

    /**
     * Define the many-to-many relationship with services.
     */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'request_services', 'request_id', 'service_id');
    }

    /**
     * Get the nurse assigned to this request.
     * Note: Nurses are managed by admins and are not users.
     */
    public function nurse()
    {
        return $this->belongsTo(Nurse::class);
    }

    /**
     * Get the user who created this request.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}