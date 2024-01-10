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
            'total_purchase_amount' => $this->sum('total_amount'),
            'total_pay_amount' => $this->sum('pay_amount'),
            'total_remain_amount' => $this->sum('remain_amount'),
            'total_purchases_list' => $this->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'voucher_no' => $purchase->voucher_no,
                    'supplier_name' => $purchase->supplier->name,
                    'item_name' => $this->getItemsName($purchase->purchase_details),
                    'branch_name' => $purchase->branch->name,
                    'total_quantity' => $purchase->total_quantity,
                    'total_amount' => $purchase->total_amount,
                    'pay_amount' => $this->getPaymentTotal($purchase),
                    'remain_amount' => $purchase->remain_amount,
                    'causer_name' => $purchase->createActivity->causer->name ?? "",
                    'payment_status' => $purchase->payment_status
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

    private function getPaymentTotal($purchase)
    {
        $total = 0;
        foreach ($purchase->payments as $payment) {
            $total += $payment->pay_amount;
        }
        return $total;
    }
}
