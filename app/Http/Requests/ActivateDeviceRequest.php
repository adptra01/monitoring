<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivateDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string', 'size:19'],
            'device' => ['required', 'array'],
            'device.fingerprint' => ['required', 'string', 'min:32', 'max:64'],
            'device.name' => ['nullable', 'string', 'max:255'],
            'device.platform' => ['nullable', 'string', 'max:50'],
            'device.platform_version' => ['nullable', 'string', 'max:50'],
            'device.app_version' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'license_key.required' => 'License key is required',
            'license_key.size' => 'License key must be in format XXXX-XXXX-XXXX-XXXX',
            'device.fingerprint.required' => 'Device fingerprint is required',
            'device.fingerprint.min' => 'Device fingerprint must be at least 32 characters',
        ];
    }
}