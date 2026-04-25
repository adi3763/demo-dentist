<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorSchedule extends Model
{
    //
     protected $fillable = ['user_id', 'day_of_week', 'start_time', 'end_time', 'is_active'];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
