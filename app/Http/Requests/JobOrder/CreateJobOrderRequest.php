<?php

namespace App\Http\Requests\JobOrder;

use Illuminate\Foundation\Http\FormRequest;

class CreateJobOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_name'         => 'required|string|max:255',
            'customer_phone'        => 'nullable|string|max:20',
            'customer_email'        => 'nullable|email',
            'origin_address'        => 'required|string',
            'origin_city'           => 'required|string|max:100',
            'origin_lat'            => 'nullable|numeric|between:-90,90',
            'origin_lng'            => 'nullable|numeric|between:-180,180',
            'destination_address'   => 'required|string',
            'destination_city'      => 'required|string|max:100',
            'destination_lat'       => 'nullable|numeric|between:-90,90',
            'destination_lng'       => 'nullable|numeric|between:-180,180',
            'cargo_type'            => 'required|string|max:100',
            'cargo_weight_kg'       => 'nullable|numeric|min:0',
            'cargo_volume_m3'       => 'nullable|numeric|min:0',
            'cargo_description'     => 'nullable|string',
            'is_hazardous'          => 'boolean',
            'pickup_scheduled_at'   => 'nullable|date',
            'delivery_scheduled_at' => 'nullable|date|after_or_equal:pickup_scheduled_at',
            'priority'              => 'in:low,normal,high,urgent',
            'estimated_cost'        => 'nullable|numeric|min:0',
            'notes'                 => 'nullable|string',
        ];
    }
}