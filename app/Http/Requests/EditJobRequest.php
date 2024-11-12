<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\ResponseHelper;

class EditJobRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function rules()
    {
        return [
            'powder_bay' => 'in:big batch,main line,small batch|nullable',
            'treatment' => 'required|string|exists:treatments,treatment',
            'colour' => 'required|string',
            'material' => 'required|string',
            'due_date' => 'required|string'
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
