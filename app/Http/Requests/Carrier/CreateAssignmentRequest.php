<?php

namespace App\Http\Requests\Carrier;

use Illuminate\Foundation\Http\FormRequest;

class CreateAssignmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'job_order_id'          => 'required|integer|exists:job_orders,id',
            'carrier_id'            => 'required|integer|exists:carriers,id',
            'vehicle_id'            => 'nullable|integer|exists:carrier_vehicles,id',
            'quoted_price'          => 'nullable|numeric|min:0',
            'agreed_price'          => 'nullable|numeric|min:0',
            'payment_term'          => 'in:cash,net7,net14,net30',
            'pickup_scheduled_at'   => 'nullable|date',
            'delivery_scheduled_at' => 'nullable|date',
            'notes'                 => 'nullable|string',
        ];
    }
}