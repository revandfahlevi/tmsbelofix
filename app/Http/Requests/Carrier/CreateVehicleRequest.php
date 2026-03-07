<?php

namespace App\Http\Requests\Carrier;

use Illuminate\Foundation\Http\FormRequest;

class CreateVehicleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'plate_number'   => 'required|string|unique:carrier_vehicles,plate_number',
            'vehicle_type'   => 'required|string|max:100',
            'brand'          => 'nullable|string|max:100',
            'max_weight_kg'  => 'nullable|numeric|min:0',
            'max_volume_m3'  => 'nullable|numeric|min:0',
            'stnk_expired_at'=> 'nullable|date',
            'kir_expired_at' => 'nullable|date',
        ];
    }
}