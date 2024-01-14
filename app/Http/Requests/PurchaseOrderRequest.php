<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class PurchaseOrderRequest extends FormRequest
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
        return [
            'supplier_id' => 'required | integer | exists:suppliers,id',
            "amount" => "required | integer | min: 1",
            "total_amount" => "required | integer | min: 1",
            'tax_percentage' => 'required | integer',
            'tax_amount' => 'required | integer',
            'discount_percentage' => 'required | integer',
            'discount_amount' => 'required | integer',
            'remark' => 'required | max:100',
            'purchase_order_details.*.item_id' => 'required|integer | exists:item_unit_details,item_id',
            'purchase_order_details.*.unit_id' => 'required|integer | exists:item_unit_details,unit_id',
            'purchase_order_details.*.item_price' => 'required|integer|min:1',
            'purchase_order_details.*.quantity' => 'required|integer|min:1',
            'purchase_order_details.*.discount_amount' => 'required |integer',
            'purchase_order_details.*.amount' => 'required | integer | min:1',
            'purchase_order_details' => 'required | array'
        ];
    }
}