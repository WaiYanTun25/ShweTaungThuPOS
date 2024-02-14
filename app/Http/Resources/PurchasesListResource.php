<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchasesListResource extends JsonResource
{
    private $report;
    private $purchase_history;
    public function __construct($resource, $purchase_history = false, $report = false)
    {
        parent::__construct($resource);
        $this->report = $report;
        $this->purchase_history = $purchase_history;
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response = [];
        if (!$this->purchase_history) {
            $response['total_purchase_amount'] = $this->total_purchase_amount;
            $response['total_pay_amount'] = $this->total_pay_amount;
            $response['total_remain_amount'] = $this->total_remain_amount;
        }

        $response += [
            'total_purchases_list' => $this->data->map(function ($purchase) {

                $result = [
                    'id' => $purchase->id,
                    'voucher_no' => $purchase->voucher_no,
                    'supplier_name' => $purchase->supplier->name,
                    'item_name' => $this->getItemsName($purchase->purchase_details),
                    'branch_name' => $purchase->branch->name,
                    'total_quantity' => $purchase->total_quantity,
                    'total_amount' => $purchase->total_amount,
                    'causer_name' => $purchase->createActivity->causer->name ?? "",
                    'payment_status' => $purchase->payment_status,
                ];

                if ($this->purchase_history)
                {
                    $result += [
                        'purchase_date' => formatToCustomDate($purchase->purchase_date),
                        'product_count' => $this->getItemCount($purchase->purchase_details),
                    ] ;
                } else {
                    $result += [
                        'remain_amount' => $purchase->remain_amount,
                        'pay_amount' => $purchase->pay_amount,
                    ];
                }
                return $result;
            }),
        ];

        if(!$this->report) {
            $response += [
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
        };
    
        return $response;
    }
    

    private function getItemsName($details)
    {
        $itemsName = [];
        foreach ($details as $detail) {
            $itemsName[] = $detail->item->item_name;
        }
        return implode(', ', $itemsName);
    }

    private function getItemCount($details)
    {
        $uniqueItemIds = collect($details)->pluck('item_id')->unique()->values()->count();
        // $uniqueItemIdsArray = $uniqueItemIds->toArray();

        return $uniqueItemIds;
    }
}
