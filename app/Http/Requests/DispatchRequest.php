<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\ResponseHelper;

class DispatchRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'dispatch_status' => $this->transform('dispatch_status', $this->dispatch_status),
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'dispatch_status' => 'boolean',
            'customer_name'=>'required',
            'customer_email'=>'string|required|email'
        ];
    }
    /**
     *  overriding the failedValidation function to validate api
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(ResponseHelper::errorResponse($validator->errors(), config('constant.status_code.not_found')));
    }

    protected function transform($key, $value)
    {
        if ($value === 'true' || $value === 'TRUE')
            return true;

        if ($value === 'false' || $value === 'FALSE')
            return false;

        return $value;
    }
}
