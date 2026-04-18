<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'specialization',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password'  => 'hashed',
        'is_active' => 'boolean',
    ];

    // Role check helpers — use these everywhere
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDoctor(): bool
    {
        return $this->role === 'doctor';
    }
}
