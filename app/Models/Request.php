<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Request extends Model
{
    use SoftDeletes;
    use HasFactory, HasTranslations;

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
        'category_id',
        'nurse_id',
        'area_id',
        'status',
        'scheduled_time',
        'ending_time',
        'location',
        'time_type',
        'problem_description',
        'nurse_gender',
        'first_name',
        'last_name',
        'full_name',
        'phone_number',
        'name', // Optional request name/title
        'discount_percentage',
        'total_price',
        'discounted_price',
        // Address fields
        'use_saved_address',
        'address_city',
        'address_street',
        'address_building',
        'address_additional_information',
        'additional_information', // Optional text for all categories
        // Category 2: Tests specific fields
        'test_package_id',
        'test_id', // For individual test requests
        'request_details_files',
        'notes',
        'request_with_insurance',
        'attach_front_face',
        'attach_back_face',
        // Category 3: Rays specific fields
        'ray_id',
        'machine_id',
        'from_date',
        'to_date',
        // Category 5: Physiotherapists specific fields
        'physiotherapist_id',
        'sessions_per_month',
        'machines_included',
        'physio_machines',
        // Category 7: Duties specific fields
        'nurse_visit_id',
        'duty_id',
        'babysitter_id',
        'visits_per_day',
        'duration_hours',
        'is_continuous_care',
        'is_day_shift',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_time' => 'datetime',
        'ending_time' => 'datetime',
        'from_date' => 'date', // Category 4: Machine rental start date, Category 5: Physiotherapist start date
        'to_date' => 'date', // Category 4: Machine rental end date, Category 5: Physiotherapist end date
        'machines_included' => 'boolean', // Category 5: Whether physio machines are included
        'physio_machines' => 'array', // Category 5: Array of physio machine data
        'sessions_per_month' => 'integer', // Category 5: Number of sessions per month
        // Category 7: Duties specific casts
        'visits_per_day' => 'integer', // Category 7: Nurse Visits - visits per day (1-4)
        'duration_hours' => 'integer', // Category 7: Duties/Babysitter - duration in hours
        'is_continuous_care' => 'boolean', // Category 7: Duties - continuous care
        'is_day_shift' => 'boolean', // Category 7: Duties/Babysitter - day shift or night shift
        'discount_percentage' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'request_with_insurance' => 'boolean',
        'request_details_files' => 'array',
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
     * Get the area for this request.
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Get the category for this request.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the test package for this request (Category 2 only).
     */
    public function testPackage()
    {
        return $this->belongsTo(TestPackage::class);
    }

    /**
     * Get the test for this request (Category 2 only - individual test).
     */
    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    /**
     * Get the ray for this request (Category 3 only).
     */
    public function ray()
    {
        return $this->belongsTo(\App\Models\Ray::class);
    }

    /**
     * Get the machine for this request (Category 4 only).
     */
    public function machine()
    {
        return $this->belongsTo(\App\Models\Machine::class);
    }

    /**
     * Get the physiotherapist for this request (Category 5 only).
     */
    public function physiotherapist()
    {
        return $this->belongsTo(\App\Models\Physiotherapist::class);
    }

    /**
     * Get the nurse visit for this request (Category 7 only - Nurse Visits subcategory).
     */
    public function nurseVisit()
    {
        return $this->belongsTo(\App\Models\NurseVisit::class);
    }

    /**
     * Get the duty for this request (Category 7 only - Duties subcategory).
     */
    public function duty()
    {
        return $this->belongsTo(\App\Models\Duty::class);
    }

    /**
     * Get the babysitter for this request (Category 7 only - Babysitter subcategory).
     */
    public function babysitter()
    {
        return $this->belongsTo(\App\Models\Babysitter::class);
    }

    /**
     * Get the chat thread for this request.
     */
    public function chatThread()
    {
        return $this->hasOne(ChatThread::class);
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

    /**
     * Get the final price after discount (if any).
     */
    public function getFinalPrice(): float
    {
        return $this->discounted_price ?? $this->total_price ?? 0.0;
    }

    /**
     * Check if this request has a discount applied.
     */
    public function hasDiscount(): bool
    {
        return $this->discount_percentage !== null && $this->discount_percentage > 0;
    }

    /**
     * Get the discount amount in currency.
     */
    public function getDiscountAmount(): float
    {
        if (!$this->hasDiscount() || !$this->total_price) {
            return 0.0;
        }

        return ($this->total_price * $this->discount_percentage) / 100;
    }
}