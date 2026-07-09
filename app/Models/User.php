<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'last_seen', // Sinkron dengan middleware tracker
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_seen' => 'datetime', // Konversi instan ke Carbon instance
        ];
    }

    /**
     * STATUS REALTIME CHECKER
     * Memantau keaktifan user dalam 2 menit terakhir
     */
    public function isOnline(): bool
    {
        return $this->last_seen && $this->last_seen->gt(now()->subMinutes(2));
    }

    /**
     * ROLE CHECKER FUNCTIONS (SINKRON 4 OTORITAS SISTEM)
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    public function isPemilik(): bool
    {
        return $this->role === 'pemilik';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }
}