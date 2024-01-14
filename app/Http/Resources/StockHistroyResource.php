<?php

namespace App\Http\Resources;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockHistroyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'stock_history_list' => $this->getCollection()->map(function ($transfer) {
                $branchName = $this->getBranchName($transfer->branch_id);
                return [
                    'id' => $transfer->id,
                    'type' => $transfer->type, // issue or recieve depend on comming rows
                    'causer_name' => $transfer->createActivity->causer->name,
                    'total_quantity' => $transfer->transfer_details->sum('quantity'),
                    'branch_name' => $branchName,
                    'transaction_date' => formatToCustomDate($transfer->transaction_date),
                    'voucher_no' => $transfer->voucher_no,
                    // 'item_names' => $this->getItemNames($transfer->transfer_details),
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

    private function getBranchName($branchId)
    {
        return Branch::find($branchId)->name;
    }
}
