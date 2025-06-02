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
