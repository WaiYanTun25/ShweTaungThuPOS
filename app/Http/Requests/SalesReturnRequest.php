<?php

namespace App\Http\Requests;

use App\Models\Inventory;
use App\Models\SalesReturn;
use App\Models\SalesReturnDetail;
use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Auth;

class SalesReturnRequest extends FormRequest
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
            'customer_id' => 'required | integer | exists:customers,id',
            'customer_name' => 'nullable',
            'amount' => 'required | integer | min:1',
            'total_amount' => 'required | integer | min:1',
            'tax_percentage' => 'required | integer',
            'tax_amount' => 'required | integer',
            'discount_percentage' => 'required | integer',
            'discount_amount' => 'required | integer',
            'remark' => 'required | max:100',
            // 'amount_type' => 'required | in:wholesale_price,vip_price,retail_price',
            'amount_type' => 'required | in:RETAIL,WHOLESALE,VIP',
            'pay_amount' => 'required | min:1',
            'payment_method' => 'required | exists:payment_methods,id',
            'sales_return_details.*.item_id' => 'required|integer | exists:items,id',
            'sales_return_details.*.unit_id' => 'required|integer | exists:units,id',
            'sales_return_details.*.item_price' => 'required|integer|min:1',
            'sales_return_details.*.quantity' => 'required|integer|min:1',
            'sales_return_details.*.discount_amount' => 'required |integer',
            'sales_return_details.*.amount' => 'required | integer | min:1',
            'sales_return_details' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {

                    if ($this->isMethod('put') || $this->isMethod('patch')) {
                        $requestId = $this->route('id');
                        $salesReturnData = SalesReturn::find($requestId);
                    }

                    foreach ($value as $index => $item)
                    {
                        // Check if 'quantity' key is present in the item array
                        if (array_key_exists('quantity', $item)) {
                            $quantity = $item['quantity'];
                            // Check if 'quantity' is greater than or equal to 1
                            if ($quantity < 1) {
                                $fail("Invalid quantity for item_id: {$item['item_id']}, unit_id: {$item['unit_id']}");
                                continue; // Skip further checks for this item
                            }
                            // check item and unit exists or not
                            if($this->isMethod('put') || $this->isMethod('patch'))
                            {
                                $checkSaleReturn = SalesReturn::findOrFail($requestId);

                                $detail = SalesReturnDetail::where('item_id', $item['item_id'])->where('unit_id', $item['unit_id'])->where('sale_return_id', $checkSaleReturn->id)->first();
                                if($detail){
                                    $totalQuantity =  $quantity - $detail->quantity;
                                    $itemExists = Inventory::where('item_id', $item['item_id'])
                                    ->where('unit_id', $item['unit_id'])
                                    ->where('branch_id', $checkSaleReturn->branch_id)
                                    ->where('quantity', '>=', $totalQuantity)
                                    ->exists();
                                }else{
                                    // $itemExists = true;
                                    $itemExists = Inventory::where('item_id', $item['item_id'])
                                    ->where('unit_id', $item['unit_id'])
                                    ->where('branch_id', Auth::user()->branch_id)
                                    ->where('quantity', '>=', $quantity)
                                    ->exists();
                                }

                                

                                if (!$itemExists) {
                                    $fail("Invalid for {$attribute}.{$index}.item_id: {$item['item_id']}, unit_id: {$item['unit_id']}");
                                }
                            }
                        }
                    }
                }
            ]
        ];
    }
}
