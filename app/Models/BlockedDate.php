<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedDate extends Model
{
    //
        protected $fillable = ['user_id', 'blocked_date', 'reason'];
    protected $casts    = ['blocked_date' => 'date'];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
