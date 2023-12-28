<?php

namespace App\Http\Resources;

use App\Models\Branch;
use App\Models\Issue;
use App\Models\Receive;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\{
    Carbon,
    Str
};
use Illuminate\Support\Facades\Auth;

class TransferResourceCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request)
    {
        return [
            'issue_receive_damage_list' => $this->collection->map(function ($transfer) {
                $branchName = $this->getBranchName($transfer->branch_id);
                return [
                    'id' => $transfer->id,
                    'type' => $transfer->type, // issue or recieve depend on comming rows
                    'voucher_no' => $transfer->voucher_no,
                    'branch_name' => $branchName,
                    'total_quantity' => $transfer->total_quantity,
                    'transaction_date' => Carbon::parse($transfer->transaction_date)->format('d/m/y'),
                    'item_names' => $this->getItemNames($transfer->transfer_details),
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

    // private function getType($transfer)
    // {
    //     $voucherNo = $transfer->voucher_no;
    //     if (Str::startsWith($voucherNo, 'INV-D')) {
    //         return "Damage";
    //     } else if (Str::startsWith($voucherNo, 'INV-I')) {
    //         return "Issue";
    //     } else {
    //         return "Receive";
    //     }


    //     return null; // Handle other cases if necessary
    // }

    private function getBranchName($branchId)
    {
        return Branch::find($branchId)->name;
    }

    private function getItemNames($transfer_details)
    {
        // Assuming transfer_details is a collection of TransferDetail models
        return $transfer_details->pluck('item.item_name')->implode(', ');
    }
}
