<?php

namespace App\Http\Requests\Route;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRouteStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status' => 'required|in:approved,active,completed,cancelled',
        ];
    }
}