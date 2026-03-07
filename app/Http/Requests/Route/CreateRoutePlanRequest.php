<?php

namespace App\Http\Requests\Route;

use Illuminate\Foundation\Http\FormRequest;

class CreateRoutePlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'job_order_id'          => 'required|integer|exists:job_orders,id',
            'driver_id'             => 'nullable|integer|exists:users,id',
            'origin_address'        => 'required|string',
            'origin_lat'            => 'required|numeric|between:-90,90',
            'origin_lng'            => 'required|numeric|between:-180,180',
            'destination_address'   => 'required|string',
            'destination_lat'       => 'required|numeric|between:-90,90',
            'destination_lng'       => 'required|numeric|between:-180,180',
            'route_type'            => 'in:fastest,shortest,cheapest',
            'estimated_toll_cost'   => 'nullable|numeric|min:0',
            'notes'                 => 'nullable|string',
            'waypoints'             => 'nullable|array',
            'waypoints.*.address'   => 'required|string',
            'waypoints.*.lat'       => 'required|numeric',
            'waypoints.*.lng'       => 'required|numeric',
            'waypoints.*.type'      => 'in:pickup,delivery,rest,fuel,toll,other',
            'waypoints.*.label'     => 'nullable|string',
            'waypoints.*.estimated_stop_minutes' => 'nullable|integer|min:0',
            'waypoints.*.estimated_arrival_at'   => 'nullable|date',
        ];
    }
}