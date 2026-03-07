<?php

namespace App\Http\Requests\JobOrder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status'   => 'required|in:pending,assigned,in_progress,picked_up,in_transit,delivered,completed,cancelled,failed',
            'notes'    => 'nullable|string',
            'location' => 'nullable|string',
            'lat'      => 'nullable|numeric',
            'lng'      => 'nullable|numeric',
        ];
    }
}