<?php

namespace App\Http\Requests;

use App\Models\Inventory;
use Illuminate\Foundation\Http\FormRequest;

class IssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from_branch_id' => 'required|integer',
            'to_branch_id' => 'required|integer',
            'total_quantity' => 'required|integer|min:1',
            'item_detail' => [
                'required',
                'array',
                'min:1',
                function ($attribute, $value, $fail) {
                    foreach ($value as $item) {
                        $itemExists = Inventory::where('item_id', $item['item_id'])
                            ->where('unit_id', $item['unit_id'])
                            ->where('branch_id', $item['from_branch_id'])
                            ->where('quantity', '>=', $item['quantity'])
                            ->exists();

                        if (!$itemExists) {
                            $fail("Insufficient quantity for item_id: {$item['item_id']}, unit_id: {$item['unit_id']}");
                        }
                    }
                },
            ],
            'item_detail.*.item_id' => 'required|integer',
            'item_detail.*.unit_id' => 'required|integer',
            'item_detail.*.quantity' => 'required|integer|min:1',
            'item_detail.*.remark' => 'required|string|max:100',
        ];
    }
}
