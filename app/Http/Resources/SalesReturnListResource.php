<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesReturnListResource extends JsonResource
{
    private $report;
    public function __construct($resource, $report = false)
    {
        parent::__construct($resource);
        $this->report = $report;
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sales_return_list = $this->data->map(function ($sales_return) {
            return [
                'id' => $sales_return->id,
                'voucher_no' => $sales_return->voucher_no,
                'customer_name' => $sales_return->customer->name,
                'item_name' => $this->getItemsName($sales_return->sales_return_details),
                'branch_name' => $sales_return->branch->name,
                'total_quantity' => $sales_return->total_quantity,
                'total_amount' => $sales_return->total_amount,
                'pay_amount' => $sales_return->pay_amount,
                'causer_name' => $sales_return->createActivity->causer->name ?? "",
                'sales_return_date' => formatToCustomDate($sales_return->sales_return_date)
            ];
        });
        $total_return_count = $this->data->sum(function ($sales_return) {
            return count($sales_return->sales_return_details);
        });
        $total_return_amount = $this->data->sum(function ($sales_return) {
            return $sales_return->pay_amount;
        });

        if($this->report) {
            return [
                'total_return_count' => $total_return_count,
                'total_return_amount' => $total_return_amount,
                'sales_return_list' => $sales_return_list
            ];
        }else{
            return [
                'total_return_count' => $total_return_count,
                'total_return_amount' => $total_return_amount,
                'sales_return_list' => $sales_return_list,
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
