<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseListByProductIdResource extends JsonResource
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
        $purchase_product_list =  $this->map(function ($purchase) {
            return [
                'id' => $purchase->id,
                'voucher_no' => $purchase->voucher_no,
                'supplier_name' => $purchase->supplier->name,
                'supplier_id' => $purchase->supplier_id,
                'total_quantity' => $purchase->total_quantity,
                'purchase_date' => formatToCustomDate($purchase->purchase_date),
                'total_amount' => $purchase->total_amount,
                'causer_name' => $purchase->createActivity->causer->name,
                'payment_method_name' => $purchase->paymentMethod->name ?? "-",
            ];
        });

        if($this->report){
            return [
                'product_list' => $purchase_product_list
            ];
        }else{
            return [
                'purchase_list' => $purchase_product_list,
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
