<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class RoleRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => config('general_constants.validation_error_message'),
                'errors' => $validator->errors(),
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
            'permission_ids' => 'required|array',
        ];

        // If it's a POST request (create), add the 'required' and 'unique:roles' rules for the 'name' field
        if ($this->isMethod('post')) {
            $rules['name'] = 'required|unique:roles';
        }

        // If it's a PUT or PATCH request (update), only add the 'required' rule for the 'name' field
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['name'] = 'required';
        }

        return $rules;
    }
}
