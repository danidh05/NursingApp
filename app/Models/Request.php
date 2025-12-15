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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_time' => 'datetime',
        'ending_time' => 'datetime',
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