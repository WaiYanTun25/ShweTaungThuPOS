<?php

namespace App\Http\Requests;

use App\Models\Inventory;
use App\Models\ItemUnitDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Auth;

class UnitConvertRequest extends FormRequest
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
            'item_id' => 'required | integer',
            'convert_details.from_unit_id' => [
                'required', 'integer',
                function ($attribute, $value, $fail) {
                    $checkItemUnit = ItemUnitDetail::where('item_id', $this->item_id)->where('unit_id', $value)->first();
                    if(!$checkItemUnit) {
                        $fail("Invalid unit_id: {$value}");
                    }
                }
            ],
            'convert_details.to_unit_id' => [
                'required', 'integer',
                function ($attribute, $value, $fail) {
                    $checkItemUnit = ItemUnitDetail::where('item_id', $this->item_id)->where('unit_id', $value)->first();
                    if(!$checkItemUnit) {
                        $fail("Invalid unit_id: {$value}");
                    }
                }
            ],
            'convert_details.from_qty' => 'required | integer',
            'convert_details.to_qty' => 'required | integer',
            'convert_details' => [
                'required',
                function ($attribute, $value, $fail) {
                    $branch_id = Auth::user()->branch_id;

                    // Check if the inventory exists for the given item, branch, and unit
                    $checkInventory = Inventory::where('item_id', $this->item_id)
                        ->where('branch_id', $branch_id)
                        ->where('unit_id', $this->convert_details['from_unit_id'])
                        ->first();
                    if (!$checkInventory) {
                        // If inventory doesn't exist, fail with an error message
                        $fail("Invalid quantity for item_id: {$this->item_id}");
                    } elseif($checkInventory->quantity < $this->quantity) {
                        // If the quantity in inventory is less than the requested quantity, fail with an error message
                        $fail("Invalid quantity for item_id: {$this->item_id}");

                    } else {
                        if ($checkInventory->quantity < $this->quantity) {
                            // If the quantity in inventory is less than the requested quantity, fail with an error message
                            $fail("Invalid quantity for item_id: {$this->item_id}");
                        }
                    }
                }
            ]
        ];
    }
}
