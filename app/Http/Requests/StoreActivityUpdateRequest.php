<?php
// app/Http/Requests/StoreActivityUpdateRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityUpdateRequest extends FormRequest
{
    public function authorize()
    {
        $activity = $this->route('activity');
        
        // Allow if user is admin, creator, or assignee
        return auth()->user()->is_admin || 
               $activity->created_by === auth()->id() || 
               $activity->assigned_to === auth()->id();
    }

    public function rules()
    {
        return [
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'status.required' => 'Status is required.',
            'status.in' => 'Invalid status selected.',
            'remarks.max' => 'Remarks cannot exceed 1000 characters.',
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'user_id' => auth()->id(),
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent(),
        ]);
    }
}