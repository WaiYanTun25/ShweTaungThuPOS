<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueReceiveDetailResource extends JsonResource
{
    protected $resourceType;
    public function __construct($resource, $resourceType = 'ISSUE')
    {
        parent::__construct($resource);
        $this->resourceType = $resourceType;
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->resourceType,
            'voucher_no' => $this->voucher_no,
            'transaction_date_mm' => convertToMyanmarDate($this->transaction_date),
            'transaction_date_eng' => formatToCustomDate_FullYear($this->transaction_date),
            'branch_name' => $this->resourceType == "ISSUE" ? $this->issuesTo->name : $this->receiveFrom->name, 
            'total_quantity' => $this->total_quantity,
            'item_details' => TransferDetailResource::collection($this->transfer_details),
        ];
    }
}
