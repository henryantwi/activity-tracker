<?php
// app/Http/Requests/StoreActivityRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreActivityRequest extends FormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'priority' => 'required|in:low,medium,high',
            'category' => 'required|in:development,testing,documentation,meeting,research,maintenance,other',
            'due_date' => 'nullable|date|after_or_equal:today',
            'assigned_to' => 'nullable|exists:users,id',
            'created_by' => 'required|exists:users,id',
            'metadata' => 'nullable|array',
            'metadata.sms_count' => 'nullable|integer|min:0',
            'metadata.log_count' => 'nullable|integer|min:0',
            'metadata.reference_number' => 'nullable|string|max:100',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Activity title is required.',
            'description.required' => 'Activity description is required.',
            'priority.required' => 'Priority level is required.',
            'category.required' => 'Activity category is required.',
            'due_date.after_or_equal' => 'Due date cannot be in the past.',
            'assigned_to.exists' => 'Selected user does not exist.',
        ];
    }

    public function prepareForValidation()
    {
        // Auto-assign to current user if not specified
        if (!$this->has('assigned_to') || empty($this->assigned_to)) {
            $this->merge([
                'assigned_to' => Auth::id(),
            ]);
        }

        // Set created_by to current user
        $this->merge([
            'created_by' => Auth::id(),
        ]);
    }
}
