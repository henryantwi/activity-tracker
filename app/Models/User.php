<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'department',
        'position',
        'bio',
        'is_admin',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_activity' => 'datetime',
        'is_admin' => 'boolean',
    ];

    // Relationships
    public function createdActivities()
    {
        return $this->hasMany(Activity::class, 'created_by');
    }

    public function assignedActivities()
    {
        return $this->hasMany(Activity::class, 'assigned_to');
    }

    public function activityUpdates()
    {
        return $this->hasMany(ActivityUpdate::class);
    }

    public function handoversFrom()
    {
        return $this->hasMany(DailyHandover::class, 'from_user_id');
    }

    public function handoversTo()
    {
        return $this->hasMany(DailyHandover::class, 'to_user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNotNull('last_activity')
                    ->where('last_activity', '>=', now()->subDays(7));
    }

    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeManagers($query)
    {
        return $query->where('role', 'manager');
    }

    // Role-based methods
    public function isAdmin()
    {
        return $this->role === 'admin' || $this->is_admin;
    }

    public function isManager()
    {
        return $this->role === 'manager';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    public function canManageReports()
    {
        return $this->isAdmin() || $this->isManager();
    }

    public function canSearchAllActivities()
    {
        return $this->isAdmin() || $this->isManager();
    }

    public function canViewAllUpdates()
    {
        return $this->isAdmin() || $this->isManager();
    }

    // Methods
    public function getFullNameAttribute()
    {
        return $this->name;
    }

    public function updateLastActivity()
    {
        $this->update(['last_activity' => now()]);
    }

    public function getTodayActivitiesCount()
    {
        return $this->assignedActivities()
                   ->whereDate('created_at', today())
                   ->count();
    }

    public function getPendingActivitiesCount()
    {
        return $this->assignedActivities()
                   ->where('status', 'pending')
                   ->count();
    }
}
