<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DamageResourceCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($transfer) {
                return [
                    'id' => $transfer->id,
                    'voucher_no' => $transfer->voucher_no,
                    'branch_id' => $transfer->branch_id,
                    'total_quantity' => $transfer->total_quantity,
                    'transaction_date' => $transfer->transaction_date,
                    'transfer_details' => TransferDetailResource::collection($transfer->transfer_details),
                ];
            }),
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
