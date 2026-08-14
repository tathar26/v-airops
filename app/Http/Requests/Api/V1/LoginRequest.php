<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['nullable', 'string', 'max:255'],
            'callsign' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Configure the validator instance to require at least email or callsign.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (empty($this->input('email')) && empty($this->input('callsign'))) {
                $v->errors()->add('identifier', 'Email or callsign is required.');
            }
        });
    }
}
