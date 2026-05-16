<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeactivateDeviceRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'license_key.required' => 'Kunci lisensi wajib diisi',
            'license_key.size' => 'Kunci lisensi harus dalam format XXXX-XXXX-XXXX-XXXX',
            'device.fingerprint.required' => 'Sidik jari perangkat wajib diisi',
            'device.fingerprint.min' => 'Sidik jari perangkat minimal 32 karakter',
        ];
    }
}
