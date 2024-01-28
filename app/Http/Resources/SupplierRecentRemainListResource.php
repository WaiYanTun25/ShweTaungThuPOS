<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierRecentRemainListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "recent_purchase_remain_list" => $this->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'voucher_no' => $purchase->voucher_no,
                    'item_name' => $this->getItemsName($purchase->purchase_details),
                    'branch_name' => $purchase->branch->name,
                    // 'total_quantity' => $purchase->total_quantity,
                    'total_amount' => $purchase->total_amount,
                    // 'pay_amount' => $purchase->pay_amount,
                    'remain_amount' => $purchase->remain_amount,
                    'causer_name' => $purchase->createActivity->causer->name ?? "",
                    'payment_status' => $purchase->payment_status,
                    'purchase_date' => formatToCustomDate($purchase->purchase_date)
                ];
            }),
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $this->currentPage(),
                'from' => $this->firstItem(),
                'last_page' => $this->lastPage(),
                'links' => $this->links(),
                'path' => $this->path(),
                'per_page' => $this->perPage(),
                'to' => $this->lastItem(),
                'total' => $this->total(),
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
