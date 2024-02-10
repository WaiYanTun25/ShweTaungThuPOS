<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class CustomerRequet extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'The given data is invalid!.',
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
            'name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'township' => 'required',
            'city' => 'required',
        ];

        if ($this->isMethod('post')) {
            $rules['customer_type'] = 'required|in:General,Specific';
        }

        // If it's a PUT or PATCH request (update), only add the 'required' rule for the 'name' field
        // if ($this->isMethod('put') || $this->isMethod('patch')) {
        //     $rules['name'] = 'required';
        // }

        return $rules;
    }
}
