<?php

namespace App\Http\Resources;

use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DamageItemListResource extends ResourceCollection
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
    public function toArray(Request $request): array
    {
        $damageList = $this->collection->map(function ($transferDetail) {
            return [
                'id' => $transferDetail->id,
                'voucher_no' => $transferDetail->voucher_no,
                // 'type' => "Damage", // issue or recieve depend on comming rows
                'item_code' => $transferDetail->item->item_code,
                'item_name' => $transferDetail->item->item_name ?? "",
                'category_name' => $transferDetail->item->category->name,
                
                'branch_name' => $transferDetail->damage->branch->name,
                'quantity' => $transferDetail->quantity,
                'transaction_date' => Carbon::parse($transferDetail->damage->transaction_date)->format('d/m/y'),
                'remark' => $transferDetail->damage->remark,
            ];
        });

        if($this->report){
            return [
                'product_list' => $damageList
            ];
        }else{
            return [
                'damage_item_list' => $damageList,
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

    public function getBranchName($branchId)
    {
        return Branch::find($branchId);   
    }
}
