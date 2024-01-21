<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesListResource extends JsonResource
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
            $response['total_sales_amount'] = $this->total_sales_amount;
            $response['total_pay_amount'] = $this->total_pay_amount;
            $response['total_remain_amount'] = $this->total_remain_amount;
        }

        $response += [
            'total_sales_list' => $this->data->map(function ($sales) {
                return [
                    'id' => $sales->id,
                    'voucher_no' => $sales->voucher_no,
                    'customer_name' => $sales->customer->name,
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
}
