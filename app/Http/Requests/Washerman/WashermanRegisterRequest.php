<?php

namespace App\Http\Requests\Washerman;

use Illuminate\Foundation\Http\FormRequest;

class WashermanRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'phone' => 'required|string|max:20',
            'shop_name' => 'required|string|max:255',
            'cnic' => 'nullable|string|max:20',
            'experience' => 'nullable|string',
            'specialization' => 'nullable|string',
            'service_area' => 'nullable|string',
        ];
    }
}