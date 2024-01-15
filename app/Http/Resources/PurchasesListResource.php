<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchasesListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_purchase_amount' => $this->total_purchase_amount,
            'total_pay_amount' => $this->total_pay_amount,
            'total_remain_amount' => $this->total_remain_amount,
            'total_purchases_list' => $this->data->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'voucher_no' => $purchase->voucher_no,
                    'supplier_name' => $purchase->supplier->name,
                    'item_name' => $this->getItemsName($purchase->purchase_details),
                    'branch_name' => $purchase->branch->name,
                    'total_quantity' => $purchase->total_quantity,
                    'total_amount' => $purchase->total_amount,
                    'pay_amount' => $purchase->pay_amount,
                    'remain_amount' => $purchase->remain_amount,
                    'causer_name' => $purchase->createActivity->causer->name ?? "",
                    'payment_status' => $purchase->payment_status
                ];
            }),
            'links' => [
                'first' => $this->data->url(1),
                'last' => $this->data->url($this->data->lastPage()),
                'prev' => $this->data->previousPageUrl(),
                'next' => $this->data->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $this->data->currentPage(),
                'from' => $this->data->firstItem(),
                'last_page' => $this->data->lastPage(),
                'links' => $this->data->links(),
                'path' => $this->data->path(),
                'per_page' => $this->data->perPage(),
                'to' => $this->data->lastItem(),
                'total' => $this->data->total(),
            ],
        ];
    }

    private function getItemsName($details)
    {
        $itemsName = [];
        foreach ($details as $detail) {
            $itemsName[] = $detail->item->item_name;
        }
        return implode(', ', $itemsName);
    }
}
