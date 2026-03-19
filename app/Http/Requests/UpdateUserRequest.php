<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name'                 => ['required', 'string', 'max:255'],
            'email'                => ['required', 'email', 'max:255', "unique:users,email,{$userId}"],
            'password'             => ['nullable', 'string', 'min:8', 'confirmed'],
            'role'                 => ['required', 'in:admin,inspector,client_viewer'],
            'client_id'            => ['nullable', 'exists:clients,id'],
            'phone'                => ['nullable', 'string', 'max:50'],
            'qualifications'       => ['nullable', 'string'],
            'qualification_expiry' => ['nullable', 'date'],
            'competent_person_flag' => ['nullable', 'boolean'],
        ];
    }
}
