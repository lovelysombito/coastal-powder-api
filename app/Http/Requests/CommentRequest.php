<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\ResponseHelper;

class CommentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'user_id' => 'required',
            'object_id' => 'required',
            'object_type' => 'required|in:JOB,LINE_ITEM',
            'comment' => 'required|max:500'
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
