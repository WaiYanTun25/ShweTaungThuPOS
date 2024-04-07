<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Traits\{
    ItemTrait,
    SupplierTrait
};

class SalesReturnDetailResource extends JsonResource
{
    use ItemTrait, SupplierTrait;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vocuher_no' => $this->voucher_no,
            'payment_status' => $this->payment_status,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer->name,
            'customer_address' => $this->customer->address,
            'causer_name' => $this->createActivity->causer->name,
            'payment_method_name' => $this->paymentMethod->name ?? null,
            'payment_method_id' => $this->payment_method_id ?? null,
            'sales_date' => formatToCustomDate($this->return_date),
            'total_quantity' => $this->total_amount,
            'tax_percentage' => $this->tax_percentage,
            'tax_amount' => $this->tax_amount,
            'discount_percentage' => $this->discount_percentage,
            'discount_amount' => $this->discount_amount,
            'pay_amount' => $this->pay_amount ?? 0, 
            'amount_type' => $this->amount_type ?? null,
            'remark' => $this->remark,
            'sales_return_details' =>$this->sales_return_details->map(function ($detail) {
                return [
                    'item_id' => $detail->item_id,
                    'item_code' => $detail->item->item_code,
                    'item_name' => $detail->item->item_name,
                    'unit_id' => $detail->unit_id,
                    'unit_name' => $detail->unit->name,
                    'item_price' => $detail->item_price,
                    'quantity' => $detail->quantity,
                    'discount_amount' => $detail->discount_amount,
                    'amount' => $detail->amount,
                    'company_name' => $this->getSupplierName(explode("-", $detail->item->item_code)[0]),
                    'category_name' => $this->getCategoryName($detail->item_id),
                    'related_units' => $this->getItemRelatedData($detail->item_id)
                ];
            }),
        ];
    }
}
