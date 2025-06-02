<?php



// app/Http/Requests/ReportFilterRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'user_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'priority' => 'nullable|in:low,medium,high',
            'category' => 'nullable|in:system_monitoring,data_verification,maintenance,support,reporting,other',
            'export_format' => 'nullable|in:pdf,excel,csv',
        ];
    }

    public function messages()
    {
        return [
            'start_date.before_or_equal' => 'Start date must be before or equal to end date.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'user_id.exists' => 'Selected user does not exist.',
        ];
    }

    public function prepareForValidation()
    {
        // Set default date range if not provided
        if (!$this->has('start_date') && !$this->has('end_date')) {
            $this->merge([
                'start_date' => now()->startOfMonth()->format('Y-m-d'),
                'end_date' => now()->endOfMonth()->format('Y-m-d'),
            ]);
        }
    }
}