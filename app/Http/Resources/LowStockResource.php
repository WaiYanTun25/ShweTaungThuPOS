<?php

namespace App\Http\Resources;

use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LowStockResource extends JsonResource
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
        $lessStockList = $this->map(function ($lowStockDetail) {
            return [
                'id' => $lowStockDetail->id,
                'item_id' => $lowStockDetail->item_id,
                'unit_id' => $lowStockDetail->unit_id,
                'unit_name' => $lowStockDetail->unit->name,
                'item_code' => $lowStockDetail->item->item_code,
                'item_name' => $lowStockDetail->item->item_name ?? "",
                'category_name' => $lowStockDetail->item->category->name,
                'current_stocks' => $lowStockDetail->quantity, 
                'last_refill_date' => $lowStockDetail->transaction_date ? Carbon::parse($lowStockDetail->transaction_date)->format('d/m/y'): "",
                'branch_name' => $this->getBranchName($lowStockDetail->branch_id),
                'reorder_level' => $lowStockDetail->reorder_level,
                'reorder_period' => convertEnglishToMyanmarNumber($lowStockDetail->reorder_period). 'ရက်အတွင်း',

                // 'transaction_date' => Carbon::parse($lowStockDetail->damage->transaction_date)->format('d/m/y'),
            ];
        });

        if($this->report){
            return [
                'product_list' => $lessStockList
            ];
        }else{
            return [
                'low_stock_list' => $lessStockList,
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
        return Branch::find($branchId)->name;   
    }
}
