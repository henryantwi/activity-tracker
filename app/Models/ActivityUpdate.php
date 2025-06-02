<?php
// app/Models/ActivityUpdate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'user_id',
        'status',
        'remarks',
        'previous_data',
        'new_data',
        'update_time',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'previous_data' => 'array',
        'new_data' => 'array',
        'update_time' => 'datetime',
    ];

    // Relationships
    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('update_time', today());
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('update_time', [$startDate, $endDate]);
    }

    // Methods
    public function getFormattedUpdateTimeAttribute()
    {
        return $this->update_time->format('M d, Y H:i:s');
    }

    /**
     * Determine if the update has any changes.
     *
     * @return bool
     */
    public function hasDataChanges()
    {
        return !empty($this->previous_data) && !empty($this->new_data);
    }

    public function getChangesAttribute()
    {
        if (!$this->hasDataChanges()) {
            return [];
        }

        $changes = [];
        $previous = $this->previous_data;
        $new = $this->new_data;

        foreach ($new as $key => $value) {
            if (isset($previous[$key]) && $previous[$key] !== $value) {
                $changes[$key] = [
                    'from' => $previous[$key],
                    'to' => $value
                ];
            }
        }

        return $changes;
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

    public function getStatusIconAttribute()
    {
        return match($this->status) {
            'pending' => 'clock',
            'in_progress' => 'play-circle',
            'completed' => 'check-circle',
            'cancelled' => 'times-circle',
            default => 'circle'
        };
    }
}
