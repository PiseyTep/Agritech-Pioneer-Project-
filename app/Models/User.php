<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory;
    use HasApiTokens, Notifiable;

    // Role constants
    const ROLE_FARMER = 'farmer';
    const ROLE_ADMIN = 'admin';
    const ROLE_SUPER_ADMIN = 'super_admin';

      // Status constants
      const STATUS_ACTIVE = 'active';
      const STATUS_PENDING = 'pending';
      const STATUS_SUSPENDED = 'suspended';
  

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone_number',
        'status', 
        'approved',
        'approved_at',
        'firebase_uid', // Add this field to make it fillable
        'last_active_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'firebase_uid'       // Hide firebase_uid from JSON responses
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'approved' => 'boolean',
        'approved_at' => 'datetime',
        'last_active_at' => 'datetime'
    ];

    // Relationship to rentals
    public function rentals()
    {
        return $this->hasMany(Rental::class);
    }

    // Helper methods for roles
    public function isFarmer()
    {
        return $this->role === self::ROLE_FARMER;
    }

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSuperAdmin()
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    // Check if user has admin privileges (either admin or super_admin)
    public function hasAdminAccess()
    {
        return $this->isAdmin() || $this->isSuperAdmin();
    }
    // Status helper methods
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }
    public function isSuspended()
    {
        return $this->status === self::STATUS_SUSPENDED;
    }
    
    // In User model
    public function scopeActive($query)
    {
        return $query->where('last_active_at', '>=', now()->subDays(30));
    }

    public function scopeNewThisWeek($query)
    {
        return $query->where('created_at', '>=', now()->startOfWeek());
    }
    // Scope to filter by role
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }
    
    // Scope to get farmers
    public function scopeFarmers($query)
    {
        return $query->where('role', self::ROLE_FARMER);
    }
    
    // Scope to get admins (including super admins)
    public function scopeAdmins($query)
    {
        return $query->whereIn('role', [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN]);
    }
    
    // Scope for pending approval
    public function scopePendingApproval($query)
    {
        return $query->where('approved', false);
    }
    
    // Update last active timestamp
    public function updateLastActive()
    {
        $this->update(['last_active_at' => now()]);
    }

}