<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'doctor_id', 'patient_name', 'patient_phone', 'patient_email',
        'service_id', 'appointment_date', 'start_time', 'end_time',
        'status', 'patient_notes',
        'rescheduled_date', 'rescheduled_start_time', 'rescheduled_end_time',
        'reschedule_reason', 'reminder_sent',
    ];

    protected $casts = [
        'appointment_date'      => 'date',
        'rescheduled_date'      => 'date',
        'reminder_sent'         => 'boolean',
    ];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }
}
