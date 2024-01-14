<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrePurchaseReturnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "voucher_no" => $this->voucher_no,
            "casuser_name" => $this->createActivity->causer->name,
            'supplier_name' => $this->supplier->name,
            'supplier_address' => $this->supplier->address,
            'supplier_phone_no' => $this->supplier->phone_number,
            'amount' => $this->amount,
            'discount_percentage' => floor($this->discount_percentage),
            'discount_amount' => $this->discount_amount,
            'tax_percentage' => floor($this->tax_percentage),
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'purchase_details' => $this->purchase_details->map(function ($detail) {
                return [
                    'item_code' => $detail->item->item_code,
                    'item_id' => $detail->item_id,
                    'supplier_name' => $detail->item->supplier->name,
                    'category_name' => $detail->item->category->name,
                    'item_name' => $detail->item->item_name,
                    'item_price' => $detail->item_price,
                    'unit_id' => $detail->unit_id,
                    'unit_name' => $detail->unit->name,
                    'quantity' => $detail->quantity,
                    'discount_amount' => $detail->discount_amount,
                    'amount' => $detail->amount,
                ];
            })
        ];
    }
}
