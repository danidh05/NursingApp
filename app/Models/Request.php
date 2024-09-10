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
        'service_id',
        'status',
        'scheduled_time',
        'location',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_time' => 'datetime',
    ];

  // Define the many-to-many relationship with services
  public function services()
  {
      return $this->belongsToMany(Service::class, 'request_services', 'request_id', 'service_id');
  }
  

  // Other relationships like nurse, user, etc.
  public function nurse()
  {
      return $this->belongsTo(Nurse::class);
  }

  public function user()
  {
      return $this->belongsTo(User::class);
  }
}