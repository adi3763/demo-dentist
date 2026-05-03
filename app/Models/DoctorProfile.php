<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorProfile extends Model
{
    protected $fillable = [
        'user_id',
        'address',
        'city',
        'state',
        'pincode',
        'specialization',
        'qualification',
        'experience_years',
        'bio',
        'photo',
        'consultation_fee',
        'languages',
        'available_days',
    ];

    protected $casts = [
        'languages'     => 'array',
        'available_days'=> 'array',
    ];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
