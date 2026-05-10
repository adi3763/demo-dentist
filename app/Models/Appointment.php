<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'doctor_id',
        'patient_name',
        'patient_phone',
        'patient_email',
        'service_id',
        'appointment_date',
        'start_time',
        'end_time',
        'status',
        'patient_notes',
        'rejected_reason',
        'rescheduled_date',
        'rescheduled_start_time',
        'rescheduled_end_time',
        'reschedule_reason',
        'reminder_sent',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'appointment_date'   => 'date',
        'rescheduled_date'   => 'date',
        'reminder_sent'      => 'boolean',
        'approved_at'        => 'datetime',
        'rejected_at'        => 'datetime',
    ];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }
}
