<?php

namespace App\Http\Requests;

use App\Models\Damage;
use App\Models\Inventory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;


class DamageRequest extends FormRequest
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
        $rules =  [  
            'branch_id' => 'required|integer',
            'item_detail.*.item_id' => 'required|integer',
            'item_detail.*.unit_id' => 'required|integer',
            'item_detail.*.quantity' => 'required|integer|min:1',
            'item_detail.*.remark' => 'required|string|max:100',
            'item_detail' => [
                'required',
                'array',
                'min:1',
                function ($attribute, $value, $fail) {
                    $branchId = request('branch_id');
                    
                    foreach ($value as $item) {
                        // Check if 'quantity' key is present in the item array
                        if (array_key_exists('quantity', $item)) {
                            $quantity = $item['quantity'];
        
                            // Check if 'quantity' is greater than or equal to 1
                            if ($quantity < 1) {
                                $fail("Invalid quantity for item_id: {$item['item_id']}, unit_id: {$item['unit_id']}");
                                continue; // Skip further checks for this item
                            }
        
                            $itemExists = Inventory::where('item_id', $item['item_id'])
                                ->where('unit_id', $item['unit_id'])
                                ->where('branch_id', $branchId)
                                ->where('quantity', '>=', $quantity)
                                ->exists();
        
                            if (!$itemExists) {
                                $fail("Insufficient quantity for item_id: {$item['item_id']}, unit_id: {$item['unit_id']} for quantity: {$quantity}");
                            }
                        }
                    }
                },
            ],
        ];

        if($this->isMethod('put') || $this->isMethod('patch'))
        {
            $rules['item_detail.*.id'] = 'required|integer';
        }

        return $rules;
    }
}
