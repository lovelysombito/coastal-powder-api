<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\ResponseHelper;

class UpdateBayDateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'chem_date' => 'date_format:Y-m-d',
            'treatment_date' => 'date_format:Y-m-d',
            'burn_date' => 'date_format:Y-m-d',
            'blast_date' => 'date_format:Y-m-d',
            'powder_date' => 'date_format:Y-m-d',
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
