<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\ResponseHelper;

class QCRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'qc_status' => 'required|in:passed,failed',
            'ncr_failed_id' => 'required_if:qc_status,failed|exists:ncr_failed_options,ncr_failed_id',
            'selected_line_item_ids' => 'required_if:qc_status,failed',
            'photo' => 'required_if:qc_status,failed',
            'process' => 'exists:treatments,treatment'
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
