<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'colour' => 'nullable|string',
            'status' => 'nullable|string|in:Ready,In Progress,Awaiting QC,QC Passed,Dispatched,Complete,Partially Shipped',
            'material' => 'nullable|string|in:steel,aluminium',
            'treatment' => 'nullable|string',
            'due_date_start' => 'nullable|date_format:Y-m-d',
            'due_date_end' => 'nullable|date_format:Y-m-d',
            'client_name' => 'nullable|string',
            'po_number' => 'nullable|string',
            'invoice_number' => 'nullable|string',
            'bay' => 'nullable|string'
        ];
    }
}
