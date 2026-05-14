<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateLicenseRequest extends FormRequest
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
}
