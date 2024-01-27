<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerRecentSalesListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "recent_sales_list" => $this->map(function ($sales) {
                return [
                    'id' => $sales->id,
                    'voucher_no' => $sales->voucher_no,
                    'item_name' => $this->getItemsName($sales->sales_details),
                    'branch_name' => $sales->branch->name,
                    'total_quantity' => $sales->total_quantity,
                    'total_amount' => $sales->total_amount,
                    'pay_amount' => $sales->pay_amount,
                    'remain_amount' => $sales->remain_amount,
                    'causer_name' => $sales->createActivity->causer->name ?? "",
                    'payment_status' => $sales->payment_status
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
