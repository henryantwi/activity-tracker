<?php 

// app/Models/Activity.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'category',
        'status',
        'due_date',
        'created_by',
        'assigned_to',
        'metadata',
    ];

    protected $casts = [
        'due_date' => 'date',
        'metadata' => 'array',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function updates()
    {
        return $this->hasMany(ActivityUpdate::class)->orderBy('created_at', 'desc');
    }

    public function latestUpdate()
    {
        return $this->hasOne(ActivityUpdate::class)->latestOfMany();
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', today())
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Methods
    public function isOverdue()
    {
        return $this->due_date && 
               $this->due_date->isPast() && 
               !in_array($this->status, ['completed', 'cancelled']);
    }

    public function updateStatus($status, $remarks = null, $user = null)
    {
        $previousData = $this->toArray();
        
        $this->update(['status' => $status]);
        
        $this->updates()->create([
            'user_id' => $user ? $user->id : Auth::id(),
            'status' => $status,
            'remarks' => $remarks,
            'previous_data' => $previousData,
            'new_data' => $this->fresh()->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $this;
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'cancelled' => 'secondary',
            default => 'primary'
        };
    }

    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'low' => 'success',
            'medium' => 'warning',
            'high' => 'danger',
            default => 'secondary'
        };
    }

    public function getStatusIconAttribute()
    {
        return match($this->status) {
            'pending' => 'clock',
            'in_progress' => 'play-circle',
            'completed' => 'check-circle',
            'cancelled' => 'x-circle',
            default => 'circle'
        };
    }

    public function getPriorityIconAttribute()
    {
        return match($this->priority) {
            'low' => 'arrow-down',
            'medium' => 'arrow-right',
            'high' => 'arrow-up',
            default => 'minus'
        };
    }

    public function getCategoryIconAttribute()
    {
        return match($this->category) {
            'system_monitoring' => 'monitor',
            'data_verification' => 'database',
            'maintenance' => 'tools',
            'support' => 'help-circle',
            'reporting' => 'file-text',
            'development' => 'code',
            'testing' => 'check-square',
            'design' => 'palette',
            'documentation' => 'book',
            'research' => 'search',
            'other' => 'more-horizontal',
            default => 'activity'
        };
    }
}
