<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyHandover extends Model
{
    use HasFactory;    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'handover_date',
        'shift_summary',
        'pending_tasks',
        'important_notes',
        'activities_data',
        'handover_time',
        'is_acknowledged',
        'acknowledged_at',
    ];

    protected $casts = [
        'activities_data' => 'array',
        'handover_time' => 'datetime',
        'acknowledged_at' => 'datetime',
        'handover_date' => 'date',
        'is_acknowledged' => 'boolean',
    ];

    // Relationships
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('handover_time', today());
    }

    public function scopeUnacknowledged($query)
    {
        return $query->whereNull('acknowledged_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('from_user_id', $userId)
              ->orWhere('to_user_id', $userId);
        });
    }

    // Methods
    public function acknowledge()
    {
        $this->update([
            'acknowledged_at' => now(),
        ]);
    }    public function getActivitiesCountAttribute()
    {
        $activitiesData = $this->activities_data;
        
        // Handle different data types safely
        if (is_array($activitiesData)) {
            return count($activitiesData);
        } elseif (is_string($activitiesData)) {
            // Handle double-encoded JSON (remove extra quotes first)
            $cleaned = trim($activitiesData, '"');
            $decoded = json_decode($cleaned, true);
            
            // If it's still a string, try decoding again (for double-encoded JSON)
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            
            return is_array($decoded) ? count($decoded) : 0;
        }
        
        return 0;
    }

    public function getIsAcknowledgedAttribute()
    {
        return !is_null($this->acknowledged_at);
    }
    
    // Custom accessor to handle double-encoded JSON
    public function getActivitiesDataAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }
        
        if (is_array($value)) {
            return $value;
        }
        
        // Handle double-encoded JSON
        if (is_string($value)) {
            // Remove extra quotes if present
            $cleaned = trim($value, '"');
            $decoded = json_decode($cleaned, true);
            
            // If it's still a string, try decoding again
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            
            return is_array($decoded) ? $decoded : [];
        }
        
        return [];
    }
}