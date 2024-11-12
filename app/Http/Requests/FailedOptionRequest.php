<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\ResponseHelper;

class FailedOptionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'ncr_failed' => 'required'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'ncr_failed.required' => 'Please add ncr_failed message',
        ];
    }
    /**
     *  overriding the failedValidation function to validate api
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(ResponseHelper::errorResponse($validator->errors(), config('constant.status_code.not_found')));
    }
}
