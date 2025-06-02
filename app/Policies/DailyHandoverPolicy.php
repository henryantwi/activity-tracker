<?php

namespace App\Policies;

use App\Models\DailyHandover;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DailyHandoverPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view handovers list
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DailyHandover $handover): bool
    {
        // Admin can view all handovers
        if ($user->is_admin) {
            return true;
        }
        
        // Users can view handovers they are involved in
        return $handover->from_user_id === $user->id || $handover->to_user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create handovers
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DailyHandover $handover): bool
    {
        // Admin can update all handovers
        if ($user->is_admin) {
            return true;
        }
        
        // Only the creator can update handover (before acknowledgment)
        return $handover->from_user_id === $user->id && is_null($handover->acknowledged_at);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DailyHandover $handover): bool
    {
        // Admin can delete all handovers
        if ($user->is_admin) {
            return true;
        }
        
        // Only the creator can delete handover (before acknowledgment)
        return $handover->from_user_id === $user->id && is_null($handover->acknowledged_at);
    }

    /**
     * Determine whether the user can acknowledge the handover.
     */
    public function acknowledge(User $user, DailyHandover $handover): bool
    {
        // Admin can acknowledge any handover
        if ($user->is_admin) {
            return true;
        }
        
        // Only the recipient can acknowledge the handover
        return $handover->to_user_id === $user->id && is_null($handover->acknowledged_at);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DailyHandover $handover): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DailyHandover $handover): bool
    {
        return $user->is_admin;
    }
}
