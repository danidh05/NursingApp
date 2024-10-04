<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

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
        'ending_time',             // New field for end time.
        'location',
        'time_type',            // For UI/UX requirements.
        'problem_description',  // Optional field for detailed descriptions.
      
        'nurse_gender',         // To accommodate filtering by gender.
        'full_name',            // Added field for full name.
        'phone_number',         // Added field for phone number.
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_time' => 'datetime',
        'ending_time' => 'datetime', // Cast end_time to datetime.
    ];

    /**
     * Define the many-to-many relationship with services.
     */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'request_services', 'request_id', 'service_id');
    }

    // Define the relationship with nurses.
    public function nurse()
    {
        return $this->belongsTo(Nurse::class);
    }

    // Define the relationship with users.
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}