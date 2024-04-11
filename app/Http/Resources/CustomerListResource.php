<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerListResource extends JsonResource
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
        $customer_list = $this->map(function ($customer) {
            return [
                'id' => $customer->id,
                'customer_code' => $customer?->code ?? "-",
                'name' => $customer->name,
                'phone_no' => $customer->phone_number,
                'address' => $customer->address,
                'township_id' => $customer->township,
                'township_name' => $customer->townshipData->name,
                'city_id' => $customer->city,
                'city_name' => $customer->cityData->name,
                'join_date' => formatToCustomDate($customer->join_date),
                'debt_amount' => $customer->debt_amount,
            ];
        });

        if($this->report){
            return [
                'customer_list' => $customer_list
            ];
        }else{
            return [
                'customer_list' => $customer_list,
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
