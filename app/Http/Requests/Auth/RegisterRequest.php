<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $rules = [
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email|max:255',
            'phone'       => 'nullable|string|max:20',
            'password'    => 'required|string|min:8|confirmed',
            'role'        => ['required', Rule::in(['admin', 'driver', 'user'])],
            'employee_id' => 'nullable|string|unique:users,employee_id',
        ];

        if ($this->input('role') === 'driver') {
            $rules['driver_profile']                  = 'required|array';
            $rules['driver_profile.license_number']   = 'required|string|unique:driver_profiles,license_number';
            $rules['driver_profile.license_type']     = 'required|string|in:SIM_A,SIM_B1,SIM_B2,SIM_C';
            $rules['driver_profile.license_expiry']   = 'required|date|after:today';
            $rules['driver_profile.vehicle_type']     = 'nullable|string|max:100';
        }

        return $rules;
    }
}