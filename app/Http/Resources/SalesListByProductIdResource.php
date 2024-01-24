<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesListByProductIdResource extends JsonResource
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
        $sales_product_list =  $this->map(function ($purchase) {
            return [
                'id' => $purchase->id,
                'voucher_no' => $purchase->voucher_no,
                'customer_name' => $purchase->customer->name,
                'customer_id' => $purchase->customer_id,
                'customer_type' => $purchase->customer->customer_type,
                'total_quantity' => $purchase->total_quantity,
                'purchase_date' => formatToCustomDate($purchase->purchase_date),
                'total_amount' => $purchase->total_amount,
                'causer_name' => $purchase->createActivity->causer->name,
                'payment_method_name' => $purchase->paymentMethod->name ?? "-",
                'payment_status' => $purchase->payment_status,
            ];
        });

        if($this->report){
            return [
                'sales_list' => $sales_product_list
            ];
        }else{
            return [
                'sales_list' => $sales_product_list,
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
    }
}
