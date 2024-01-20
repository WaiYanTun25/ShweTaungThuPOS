<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'voucher_no' => $this->voucher_no,
            'payment_status' => $this->payment_status,
            'supplier_name' => $this->supplier->name,
            'supplier_address' => $this->supplier->address,
            'causer_name' => $this->createActivity->causer->name,
            'branch_id' => $this->branch_id,
            'branch_name' => $this->branch->name,
            'branch_address' => $this->branch->address,
            'branch_phone_no' => $this->branch->phone_number,
            'payment_method_name' => $this->paymentMethod->name ?? null,
            'payment_method_id' => $this->payment_method_id ?? null,
            'purchase_date' => formatToCustomDate($this->purchase_date),
            'total_quantity' => $this->total_amount,
            'tax_percentage' => $this->tax_percentage,
            'tax_amount' => $this->tax_amount,
            'discount_percentage' => $this->discount_percentage,
            'discount_amount' => $this->discount_amount,
            // 'total_payment_amount' => $this->payment->pay_amount,
            'pay_amount' => $this->pay_amount ?? 0,
            'remain_amount' => $this->remain_amount,
            'purchase_details' =>$this->purchase_details->map(function ($detail) {
                    return [
                        'item_id' => $detail->item_id,
                        'item_code' => $detail->item->item_code,
                        'unit_id' => $detail->unit_id,
                        'item_name' => $detail->item->item_name,
                        'unit_name' => $detail->unit->name,
                        'item_price' => $detail->item_price,
                        'quantity' => $detail->quantity,
                        'discount_amount' => $detail->discount_amount,
                        'amount' => $detail->amount,
                    ];
                }),
        ];
    }
}
