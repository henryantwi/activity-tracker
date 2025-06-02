<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ActivityPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view activities (filtered in controller)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Activity $activity): bool
    {
        // Admins can view all activities
        if ($user->is_admin) {
            return true;
        }
        
        // Users can view activities they created or are assigned to
        return $activity->created_by === $user->id || $activity->assigned_to === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create activities
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Activity $activity): bool
    {
        // Admins can update all activities
        if ($user->is_admin) {
            return true;
        }
        
        // Users can update activities they created or are assigned to
        return $activity->created_by === $user->id || $activity->assigned_to === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Activity $activity): bool
    {
        // Admins can delete all activities
        if ($user->is_admin) {
            return true;
        }
        
        // Only creators can delete their own activities
        return $activity->created_by === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Activity $activity): bool
    {
        // Same logic as delete
        return $this->delete($user, $activity);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Activity $activity): bool
    {
        // Only admins can force delete
        return $user->is_admin;
    }
}
