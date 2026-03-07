<?php

namespace App\Http\Requests\Carrier;

use Illuminate\Foundation\Http\FormRequest;

class CreateCarrierRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'      => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email',
            'address'   => 'nullable|string',
            'city'      => 'nullable|string|max:100',
            'pic_name'  => 'nullable|string|max:255',
            'pic_phone' => 'nullable|string|max:20',
            'type'      => 'required|in:trucking,shipping,airline,courier,own_fleet',
            'notes'     => 'nullable|string',
        ];
    }
}