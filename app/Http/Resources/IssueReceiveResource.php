<?php

namespace App\Http\Resources;

use App\Models\Branch;
use App\Models\Issue;
use App\Models\Receive;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\{
    Carbon,
};
use Illuminate\Support\Facades\Auth;

class IssueReceiveResource extends ResourceCollection
{
    private $report;
    public function __construct($resource, $report = false)
    {
        parent::__construct($resource);
        $this->report = $report;
    }
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request)
    {
        $issue_receive_list = $this->collection->map(function ($transfer) {
            return [
                'id' => $transfer->id,
                'type' => $transfer->type, // issue or recieve depend on comming rows
                'voucher_no' => $transfer->voucher_no,
                'from_branch' => $this->getBranchName($transfer->from_branch_id),
                'to_branch' => $this->getBranchName($transfer->to_branch_id),
                'total_quantity' => $transfer->total_quantity,
                'transaction_date' => Carbon::parse($transfer->transaction_date)->format('d/m/y'),
                'item_names' => $this->getItemNames($transfer->transfer_details),
            ];
        });

        if($this->report){
            return [
                'product_list' => $issue_receive_list
            ];
        }else{
            return [
                'issue_receive_list' => $issue_receive_list,
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
