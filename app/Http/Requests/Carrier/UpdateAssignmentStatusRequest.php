<?php

namespace App\Http\Requests\Carrier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssignmentStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status'       => 'required|in:sent,confirmed,rejected,in_progress,completed,cancelled',
            'agreed_price' => 'nullable|numeric|min:0',
            'reason'       => 'nullable|string',
            'notes'        => 'nullable|string',
        ];
    }
}