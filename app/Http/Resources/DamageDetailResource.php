<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DamageDetailResource extends JsonResource
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
            'remark' => $this->remark,
            'voucher_no' => $this->voucher_no,
            'transaction_date_mm' => convertToMyanmarDate($this->transaction_date),
            'transaction_date_eng' => formatToCustomDate_FullYear($this->transaction_date),
            'total_quantity' => $this->transfer_details->sum('quantity'),
            'item_details' => TransferDetailResource::collection($this->transfer_details)
        ];
    }
}
