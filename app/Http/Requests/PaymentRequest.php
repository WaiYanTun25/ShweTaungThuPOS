<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class PaymentRequest extends FormRequest
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
        $type = $this->route('type');
        $commonRules = [
            'amount' => 'required | integer | min:1',
                'payment_method_id' => 'required | integer | exists:payment_methods,id',
                'payment_date' => 'required',
                // 'causer_name' => 'required',
                // 'causer_id' => 'required | integer | exists:users,id',
        ];

        if($type == 'customers') {
            $customerRules = [
                'customer_id' => 'required | integer | exists:customers,id',
                
            ];
            return array_merge($commonRules, $customerRules);
        }else{
            $customerRules = [
                'supplier_id' => 'required | integer | exists:suppliers,id',
            ];

            return array_merge($commonRules, $customerRules);
        }
        return $commonRules;
    }
}
