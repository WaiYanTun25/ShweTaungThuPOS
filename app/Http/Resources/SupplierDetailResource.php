<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'city' => $this->cityData->name,
            'township' => $this->townshipData->name,
            'phone_number' => $this->phone_number,
            'customer_type' => $this->customer_type,
            'join_date' => formatToCustomDate($this->join_date),
            'debt_amount' => $this->getDebtAmount()
        ];
    }
}
