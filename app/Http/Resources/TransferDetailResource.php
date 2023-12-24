<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferDetailResource extends JsonResource
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
            'voucher_no' => $this->voucher_no,
            'item_id' => $this->item_id,
            'item_name' => $this->item->item_name,
            'unit_id' => $this->unit_id,
            'unit_name' => $this->unit->name,
            'quantity' => $this->quantity,
            'remark' => $this->remark,
        ];
    }
}