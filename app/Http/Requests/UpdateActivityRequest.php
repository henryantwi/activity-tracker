<?php
// app/Http/Requests/UpdateActivityRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateActivityRequest extends FormRequest
{
    public function authorize()
    {
        $activity = $this->route('activity');
        $user = Auth::user();
        
        // Allow if user is admin, manager, creator, or assignee
        return $user->isAdmin() ||
               $user->isManager() ||
               $activity->created_by === Auth::id() ||
               $activity->assigned_to === Auth::id();
    }

    public function rules()
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|max:2000',
            'priority' => 'sometimes|required|in:low,medium,high',
            'category' => 'sometimes|required|in:development,testing,documentation,meeting,research,maintenance,other',
            'status' => 'sometimes|required|in:pending,in_progress,completed,cancelled',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'metadata' => 'nullable|array',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Activity title is required.',
            'description.required' => 'Activity description is required.',
            'priority.required' => 'Priority level is required.',
            'category.required' => 'Activity category is required.',
            'status.required' => 'Status is required.',
            'assigned_to.exists' => 'Selected user does not exist.',
        ];
    }
}
