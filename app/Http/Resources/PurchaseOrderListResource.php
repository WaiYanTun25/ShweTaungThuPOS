<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderListResource extends JsonResource
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
        $purchase_order_list = $this->map(function ($order) {
            return [
                'id' => $order->id,
                'is_lock' => $order->is_lock,
                'voucher_no' => $order->voucher_no,
                'supplier_name' => $order->supplier->name,
                'item_name' => $this->getItemsName($order->purchase_order_details),
                'total_quantity' => $order->total_quantity,
                'total_amount' => $order->total_amount,
                'causer_name' => $order->createActivity?->causer?->name,
            ];
        });
        
        if($this->report){
            return [
                'total_order_list' => count($purchase_order_list),
                'purchase_order_list' => $purchase_order_list
            ];
        }else{
            return [
                'total_order_list' => $this->total(),
                'purchase_order_list' => $purchase_order_list,
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

    private function getItemsName($details)
    {
        $itemsName = [];
        foreach ($details as $detail) {
            $itemsName[] = $detail->item->item_name;
        }
        return implode(', ', $itemsName);
    }
}
