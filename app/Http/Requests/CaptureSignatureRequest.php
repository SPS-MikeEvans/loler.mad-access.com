<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CaptureSignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->isAdmin() || auth()->user()?->isInspector();
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'digital_signature' => ['required', 'string'],
            'signed_by' => ['required', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'digital_signature.required' => 'A signature is required.',
            'signed_by.required' => 'Please enter the name of the person signing.',
        ];
    }
}
