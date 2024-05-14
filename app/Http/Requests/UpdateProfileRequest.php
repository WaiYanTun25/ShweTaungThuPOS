<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use stdClass;

class UpdateProfileRequest extends FormRequest
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
        $rules = [
            'name' => 'required',
            // 'address' => 'required',
            'phone_number' => 'required',
            // 'role_id' => 'required | exists:roles,id',
            // 'password' => ['required', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
            'branch_id' => 'required',
        ];

        // if ($this->method() == 'PUT') {
        //     return $this->updateRules();
        // } else {
        //     return $this->createRules();
        // }

        return $rules;
    }
}
