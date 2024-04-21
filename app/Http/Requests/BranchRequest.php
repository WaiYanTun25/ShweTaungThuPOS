<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use stdClass;

class BranchRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        $firstError = $validator->errors()->first();
        throw new HttpResponseException(
            response()->json([
                'message' => $firstError,
                'errors' => new stdClass(),
            ], 422)
        );
    }

    // protected function failedValidation(Validator $validator)
    // {
    //     throw new HttpResponseException(
    //         response()->json([
    //             'message' => 'The given data is invalid!.',
    //             'errors' => $validator->errors(),
    //         ], 422)
    //     );
    // }
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
        $createRules = [
            "name" => 'required | unique:branches',
            "phone_number" => "required",
            "address" => 'required'
        ];

        $updateRules = [
            "name" => 'required|unique:branches,name,' . $this->route('branch'),
            "phone_number" => "required",
            "address" => 'required'
        ];

        return $this->method() === 'PUT' ? $updateRules : $createRules;
        // if ($this->method() == 'PUT') {
        //     return $updateRules;
        // }else{
        //     return $createRules;
        // }
    }
}
