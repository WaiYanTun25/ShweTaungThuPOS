<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierListResource extends JsonResource
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
        $supplier_list = $this->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'supplier_code' => $supplier?->code ?? "-",
                'prefix' => $supplier->prefix,
                'name' => $supplier->name,
                'phone_no' => $supplier->phone_number,
                'township_id' => (int)$supplier->township,
                'township_name' => $supplier->townshipData->name,
                'city_id' =>  (int)$supplier->city,
                'city_name' => $supplier->cityData->name,
                'join_date' => formatToCustomDate($supplier->join_date),
                'total_purchase_amount' =>  (int)$supplier->total_purchase_amount,
                'debt_amount' =>  (int)$supplier->debt_amount,
            ];
        });

        if($this->report){
            return [
                'supplier_list' => $supplier_list
            ];
        }else{
            return [
                'supplier_list' => $supplier_list,
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
