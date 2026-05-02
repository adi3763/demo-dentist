<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'service_id',
        'message',
        'ip_address',
        'status',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function markAsRead(): void
    {
        if (! $this->read_at) {
            $this->update([
                'status'  => 'read',
                'read_at' => now(),
            ]);
        }
    }
}
