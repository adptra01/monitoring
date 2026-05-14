<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CheckUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string', 'regex:/^LIC-[A-Z0-9]{8}-[A-Z0-9]{8}$/'],
            'current_version' => ['required', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'license_key.regex' => 'Format license key harus LIC-XXXXXXXX-XXXXXXXX',
        ];
    }
}
