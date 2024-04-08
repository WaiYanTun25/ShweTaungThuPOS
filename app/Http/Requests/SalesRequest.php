<?php

namespace App\Http\Requests;

use App\Models\Inventory;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SalesRequest extends FormRequest
{
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
            'customer_id' => 'required | integer | exists:customers,id',
            'customer_name' => 'nullable | string',
            'amount' => 'required | integer | min: 1',
            'total_amount' => 'required | integer',
            'tax_percentage' => 'required | integer',
            'tax_amount' => 'required | integer',
            'discount_percentage' => 'required | integer',
            'discount_amount' => 'required | integer',
            'payment_status' => 'required | in:PARTIALLY_PAID,FULLY_PAID,UN_PAID',
            'amount_type' => 'required | in:RETAIL,WHOLESALE,VIP',
            'remark' => 'required | max:100',
            'sales_details.*.item_id' => 'required | integer | exists:items,id',
            'sales_details.*.unit_id' => 'required | integer | exists:units,id',
            'sales_details.*.item_price' => 'required | integer| min:1',
            'sales_details.*.quantity' => 'required | integer| min:1',
            'sales_details.*.discount_amount' => 'required |integer',
            'sales_details.*.amount' => 'required | integer | min:1',
            'sales_details' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    $branchId = Auth::user()->branch_id;

                    // if($this->isMethod('put') || $this->isMethod('patch'))
                    // {
                    //     $requestId = $this->route('damage');
                    //     $saleData = Sale::find($requestId);  
                    // }
                    
                    foreach ($value as $item) {
                        // Check if 'quantity' key is present in the item array
                        if (array_key_exists('quantity', $item)) {
                            $quantity = $item['quantity'];
        
                            // Check if 'quantity' is greater than or equal to 1
                            if ($quantity < 1) {
                                $fail("Invalid quantity for item_id: {$item['item_id']}, unit_id: {$item['unit_id']}");
                                continue; // Skip further checks for this item
                            }
                            
                            $checkInventroies = Inventory::where('item_id', $item['item_id'])->where('unit_id', $item['unit_id'])->where('branch_id', $branchId)->first();
                            if(!$checkInventroies){
                                $fail("There is no stocks for item_id: {$item['item_id']}, unit_id: {$item['unit_id']}");
                                continue; // Skip further checks for this item
                            }

                            // if($this->isMethod('put') || $this->isMethod('patch'))
                            // {
                            //     $detail = SaleDetail::where('item_id', $item['item_id'])->where('unit_id', $item['unit_id'])->where('voucher_no', $saleData->voucher_no)->first();

                            //     if($detail){
                            //         $totalQuantity =  $quantity - $detail->quantity;
                            //         $itemExists = Inventory::where('item_id', $item['item_id'])
                            //         ->where('unit_id', $item['unit_id'])
                            //         ->where('branch_id', $branchId)
                            //         ->where('quantity', '>=', $totalQuantity)
                            //         ->exists();
                            //     }else{
                            //         $itemExists = true;
                            //     }
                            // }else{
                            //     $itemExists = Inventory::where('item_id', $item['item_id'])
                            //     ->where('unit_id', $item['unit_id'])
                            //     ->where('branch_id', $branchId)
                            //     ->where('quantity', '>=', $quantity)
                            //     ->exists();
                            // }
                            
                            // if (!$itemExists) {
                            //     $fail("Insufficient quantity for item_id: {$item['item_id']}, unit_id: {$item['unit_id']} for quantity: {$quantity}");
                            // }
                        }
                    }
                },
            ]
        ];


        if ($this->input('payment_status') === 'UN_PAID') {
            $rules['payment_method'] = 'nullable';
            $rules['pay_amount'] = 'nullable|integer';
        } else {
            // If payment_status is not UN_PAID, ensure payment_method and pay_amount are required
            $rules['payment_method'] = 'required';
            $rules['pay_amount'] = 'required|integer';
        }

        return $rules;
    }
}
