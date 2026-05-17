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
            'license_key' => ['required', 'string', 'min:5', 'max:64'],
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
            'license_key.required' => 'Kunci lisensi wajib diisi',
            'license_key.min' => 'Kunci lisensi tidak valid',
            'device.fingerprint.required' => 'Sidik jari perangkat wajib diisi',
            'device.fingerprint.min' => 'Sidik jari perangkat minimal 32 karakter',
        ];
    }
}
