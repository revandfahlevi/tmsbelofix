<?php

namespace App\Http\Requests\Gps;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'lat'          => 'required|numeric|between:-90,90',
            'lng'          => 'required|numeric|between:-180,180',
            'accuracy'     => 'nullable|numeric|min:0',
            'speed_kmh'    => 'nullable|numeric|min:0',
            'heading'      => 'nullable|numeric|between:0,360',
            'battery_level'=> 'nullable|integer|between:0,100',
            'job_order_id' => 'nullable|integer|exists:job_orders,id',
            'recorded_at'  => 'nullable|date',
        ];
    }
}