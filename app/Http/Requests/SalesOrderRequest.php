<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use stdClass;

class SalesOrderRequest extends FormRequest
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
        return [
            "customer_id" => 'required | integer | exists:customers,id',
            "amount" => "required | integer | min: 1",
            "total_amount" => "required | integer | min: 1",
            'tax_percentage' => 'required | integer',
            'tax_amount' => 'required | integer',
            'discount_percentage' => 'required | integer',
            'discount_amount' => 'required | integer',
            // 'amount_type' => 'required | in:wholesale_price,vip_price,retail_price',
            'amount_type' => 'required | in:RETAIL,WHOLESALE,VIP',
            'is_lock' => 'nullable | in:1,0',
            'remark' => 'required | max:100',
            'sales_order_details.*.item_id' => 'required|integer | exists:items,id',
            'sales_order_details.*.unit_id' => 'required|integer | exists:units,id',
            'sales_order_details.*.item_price' => 'required|integer|min:1',
            'sales_order_details.*.quantity' => 'required|integer|min:1',
            'sales_order_details.*.discount_amount' => 'required |integer',
            'sales_order_details.*.amount' => 'required | integer | min:1',
            'sales_order_details' => 'required | array'
        ];
    }
}
