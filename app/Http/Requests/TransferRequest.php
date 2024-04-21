<?php

namespace App\Http\Requests;

use App\Models\Inventory;
use App\Models\Issue;
use App\Models\TransferDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use stdClass;

class TransferRequest extends FormRequest
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
        $rules =  [  
            'item_details.*.item_id' => 'required|integer| exists:items,id',
            'item_details.*.unit_id' => 'required|integer| exists:units,id',
            'item_details.*.quantity' => 'required|integer|min:1',
            'item_details' => [
                'required',
                'array',
                'min:1',
                function ($attribute, $value, $fail) {
                    $fromBranchId = Auth::user()->branch_id;
                    if($this->isMethod('put') || $this->isMethod('patch'))
                    {
                        $requestId = $this->route('issue');
                        $issueData = Issue::find($requestId);  
                    }
                    foreach ($value as $item) {
                        // Check if 'quantity' key is present in the item array
                        if (array_key_exists('quantity', $item)) {
                            $quantity = $item['quantity'];
        
                            // Check if 'quantity' is greater than or equal to 1
                            if ($quantity < 1) {
                                $fail("Invalid quantity for item_id: {$item['item_id']}, unit_id: {$item['unit_id']}");
                                continue; // Skip further checks for this item
                            }

                            // check item and unit-id exists in inventrory
                            $checkInventroies = Inventory::where('item_id', $item['item_id'])->where('unit_id', $item['unit_id'])->where('branch_id', $fromBranchId)->first();

                            if(!$checkInventroies){
                                $fail("There is no stocks for item_id: {$item['item_id']}, unit_id: {$item['unit_id']}");
                                continue; // Skip further checks for this item

                            }
        
                            if($this->isMethod('put') || $this->isMethod('patch'))
                            {
                                $checkIssue = Issue::findOrFail($requestId);
                                $detail = TransferDetail::where('item_id', $item['item_id'])->where('unit_id', $item['unit_id'])->where('voucher_no', $issueData->voucher_no )->first();
                                if($detail){
                                    $totalQuantity =  $quantity - $detail->quantity;
                                    $itemExists = Inventory::where('item_id', $item['item_id'])
                                    ->where('unit_id', $item['unit_id'])
                                    ->where('branch_id', $fromBranchId)
                                    ->where('quantity', '>=', $totalQuantity)
                                    ->exists();
                                }else{
                                    $itemExists = true;
                                }
                            }else{
                                $itemExists = Inventory::where('item_id', $item['item_id'])
                                ->where('unit_id', $item['unit_id'])
                                ->where('branch_id', $fromBranchId)
                                ->where('quantity', '>=', $quantity)
                                ->exists();
                            }
                           
        
                            if (!$itemExists) {
                                $fail("Insufficient quantity for item_id: {$item['item_id']}, unit_id: {$item['unit_id']} for quantity: {$quantity}");
                            }
                        }
                    }
                },
            ],
        ];

        if($this->isMethod('post'))
        {
            $rules['to_branch_id'] = 'required|integer';
            
        }

        return $rules;
    }

    
}
