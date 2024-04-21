<?php

namespace App\Http\Requests;

use App\Models\Inventory;
use App\Models\ItemUnitDetail;
use App\Models\UnitConvert;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Auth;
use stdClass;

class UnitConvertRequest extends FormRequest
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
        $branch_id = Auth::user()->branch_id;

        return [
            'item_id' => 'required|integer',
            'convert_details.from_unit_id' => [
                'required', 'integer', function ($attribute, $value, $fail) {
                    $this->validateItemUnit($attribute, $value, $fail);
                }
            ],
            'convert_details.to_unit_id' => [
                'required', 'integer', function ($attribute, $value, $fail) {
                    $this->validateItemUnit($attribute, $value, $fail);
                }
            ],
            'convert_details.from_qty' => 'required|integer',
            'convert_details.to_qty' => 'required|integer',
            'convert_details' => [
                'required', function ($attribute, $value, $fail) use ($branch_id) {
                    $this->validateQuantity($attribute, $value, $fail, $branch_id);
                }
            ]
        ];
    }

    protected function validateItemUnit($attribute, $value, $fail)
    {
        $checkItemUnit = ItemUnitDetail::where('item_id', $this->item_id)->where('unit_id', $value)->first();
        if (!$checkItemUnit) {
            $fail("Invalid {$attribute}: {$value}");
        }
    }

    protected function validateQuantity($attribute, $value, $fail)
    {
        $branch_id = Auth::user()->branch_id;
        info($this->convert_details['from_qty']);

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $requestId = $this->route('unit_convert');
            $prevData = UnitConvert::with('convertDetail')->find($requestId);

            $fromUnitId = $this->convert_details['from_unit_id'];
            $fromQty = $this->convert_details['from_qty'];

            // Calculate the sum of previous and updated quantity
            $totalQuantity = $prevData->convertDetail->from_qty + $fromQty;

            // Check if the total quantity exceeds the current inventory quantity
            $checkInventory = Inventory::where('item_id', $this->item_id)
                ->where('branch_id', $branch_id)
                ->where('unit_id', $fromUnitId)
                ->where('quantity', '>=', $totalQuantity)
                ->exists();
        } else {
            $fromUnitId = $this->convert_details['from_unit_id'];
            $fromQty = $this->convert_details['from_qty'];

            // Check if the inventory exists for the given item, branch, and unit
            $checkInventory = Inventory::where('item_id', $this->item_id)
                ->where('branch_id', $branch_id)
                ->where('unit_id', $fromUnitId)
                ->where('quantity', '>', $fromQty)
                ->exists();
        }

        if (!$checkInventory) {
            $fail("Invalid quantity for item_id: {$this->item_id}");
        }
    }
}
