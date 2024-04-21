<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use stdClass;

class ItemRequest extends FormRequest
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
            'category_id' => 'required',
            'supplier_id' => 'required',
            'item_name' => 'required',
            'unit_detail' => 'required|array',
            'unit_detail.*.unit_id' => 'required|numeric',
            'unit_detail.*.rate' => 'required|numeric',
            'unit_detail.*.vip_price' => 'required|numeric',
            'unit_detail.*.retail_price' => 'required|numeric',
            'unit_detail.*.wholesale_price' => 'required|numeric',
            'unit_detail.*.reorder_level' => 'required|numeric',
            'unit_detail.*.reorder_period' => 'required|numeric',
        ];
    }
}
