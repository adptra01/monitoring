<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ValidateLicenseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string', 'regex:/^LIC-[A-Z0-9]{8}-[A-Z0-9]{8}$/'],
            'device_id' => ['required', 'string', 'max:255'],
            'app_version' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'license_key.regex' => 'Format license key harus LIC-XXXXXXXX-XXXXXXXX',
        ];
    }
}
