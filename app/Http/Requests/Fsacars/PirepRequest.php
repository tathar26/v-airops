<?php

namespace App\Http\Requests\Fsacars;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PirepRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user'       => 'required|string',
            'pass'       => 'required|string',
            'fhash'      => 'required|string',
            'blocktime'  => 'required|numeric',
            'airtime'    => 'required|numeric',
            'directNM'   => 'required|numeric',
            'actualNM'   => 'required|numeric',
            'fuelstart'  => 'required|numeric',
            'fuelstop'   => 'required|numeric',
            'landingFPM' => 'required|numeric',
        ];
    }

    /**
     * Handle a failed validation attempt by returning legacy plaintext error format.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response('ERR#Malformed PIREP Payload', 200)->header('Content-Type', 'text/plain')
        );
    }
}
