<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Traits\{
    ItemTrait,
    SupplierTrait
};

class SalesOrderDetailResource extends JsonResource
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
            'voucher_no' => $this->voucher_no,
            // 'payment_status' => $this->payment_status,
            'customer_name' => $this->customer->name,
            'customer_address' => $this->customer->address,
            'causer_name' => $this->createActivity->causer->name,
            'branch_id' => $this->branch_id,
            'branch_name' => $this->branch->name,
            'branch_address' => $this->branch->address,
            'branch_phone_no' => $this->branch->phone_number,
            'sales_date' => formatToCustomDate($this->sales_date),
            'total_quantity' => $this->total_amount,
            'tax_percentage' => $this->tax_percentage,
            'tax_amount' => $this->tax_amount,
            'discount_percentage' => $this->discount_percentage,
            'discount_amount' => $this->discount_amount,
            'amount_type' => $this->amount_type ?? null,
            'remark' => $this->remark,
            'sales_order_details' =>$this->sales_order_details->map(function ($detail) {
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
                        'company_name' => $this->getSupplierName(explode("-", $detail->item->item_code)[0]),
                        'category_name' => $this->getCategoryName($detail->item_id),
                        'related_units' => $this->getItemRelatedData($detail->item_id)
                    ];
                }),
        ];
    }
}
