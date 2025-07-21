<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Request extends Model
{
    use SoftDeletes;
    use HasFactory;

    // Define status constants for order tracking
    public const STATUS_SUBMITTED = 'submitted';        // User submitted the request
    public const STATUS_ASSIGNED = 'assigned';          // Admin accepted and assigned a nurse
    public const STATUS_IN_PROGRESS = 'in_progress';    // Nurse arrived (time_needed_to_arrive = 0)
    public const STATUS_COMPLETED = 'completed';        // Request finished and closed

    // Legacy status for backward compatibility
    public const STATUS_PENDING = 'pending';   // Will be mapped to SUBMITTED
    public const STATUS_APPROVED = 'approved'; // Will be mapped to ASSIGNED
    public const STATUS_CANCELED = 'canceled'; // For cancelled requests

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
        'name', // Optional request name/title
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
     * Get all valid status values for the new tracking system.
     *
     * @return array
     */
    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_SUBMITTED,
            self::STATUS_ASSIGNED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELED, // For cancelled requests
        ];
    }

    /**
     * Get status flow description.
     *
     * @return array
     */
    public static function getStatusFlow(): array
    {
        return [
            self::STATUS_SUBMITTED => 'Request submitted by user',
            self::STATUS_ASSIGNED => 'Admin accepted and assigned nurse',
            self::STATUS_IN_PROGRESS => 'Nurse arrived at location',
            self::STATUS_COMPLETED => 'Service completed successfully',
            self::STATUS_CANCELED => 'Request was cancelled',
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

    /**
     * Auto-update status when nurse arrives
     */
    public function checkAndUpdateStatusOnArrival(): void
    {
        if ($this->status === self::STATUS_ASSIGNED && $this->hasNurseArrived()) {
            $this->update(['status' => self::STATUS_IN_PROGRESS]);
        }
    }
}