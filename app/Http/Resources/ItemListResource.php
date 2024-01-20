<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;

class ItemListResource extends ResourceCollection
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
    // public function toArray(Request $request): array
    // {
    //     $produtList = $this->collection->map(function ($inventory) {
    //         return [
    //             'id' => $inventory->id,
    //             'item_id' => $inventory->item->id,
    //             'supplier_id' => $inventory->item->supplier_id,
    //             'category_id' => $inventory->item->category_id,
    //             'item_code' => $inventory->item->item_code,
    //             'item_name' => $inventory->item->item_name,
    //             'category_name' => $inventory->item->category->name,
    //             'branch_name' => $inventory->branch->name,
    //             'quantity' => $inventory->quantity,
    //             'last_refill_date' => $inventory->last_refill_date ? formatToCustomDate($inventory->last_refill_date) : null,
    //             'reorder_level' => $inventory->item->itemUnitDetails
    //                 ->filter(function ($detail) use ($inventory) {
    //                     return $detail->item_id == $inventory->item_id
    //                         && $detail->unit_id == $inventory->unit_id
    //                         && $detail->reorder_level;
    //                 })
    //                 ->first()
    //                 ->reorder_level ?? null,
    //             'reorder_period' => $inventory->item->itemUnitDetails
    //                 ->filter(function ($detail) use ($inventory) {
    //                     return $detail->item_id == $inventory->item_id
    //                         && $detail->unit_id == $inventory->unit_id
    //                         && $detail->reorder_level;
    //                 })
    //                 ->first()
    //                 ->reorder_period ?? null,
    //         ];
    //     });
        
    //     if($this->report){
    //         return [
    //             'product_list' => $produtList
    //         ];
    //     }else{
    //         return [
    //             'product_list' => $produtList,
    //             'links' => [
    //                 'first' => $this->url(1),
    //                 'last' => $this->url($this->lastPage()),
    //                 'prev' => $this->previousPageUrl(),
    //                 'next' => $this->nextPageUrl(),
    //             ],
    //             'meta' => [
    //                 'current_page' => $this->currentPage(),
    //                 'from' => $this->firstItem(),
    //                 'last_page' => $this->lastPage(),
    //                 'links' => $this->links(),
    //                 'path' => $this->path(),
    //                 'per_page' => $this->perPage(),
    //                 'to' => $this->lastItem(),
    //                 'total' => $this->total(),
    //             ],
    //         ];
    //     }
    // }

    public function toArray(Request $request): array
    {
        $produtList = $this->collection->map(function ($item) {
            return [
                'item_id' => $item->id,
                'supplier_id' => $item->supplier_id,
                'supplier_name' => $item->supplier->name,
                'category_id' => $item->category_id,
                'category_name' => $item->category->name,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                // 'branch_name' => $item->branch->name, // no need becoze we have branch_with_quantities
                'quantity' => $item->inventories->sum('quantity'),
                // 'last_refill_date' => $item->inventories[0]?->last_refill_date ? formatToCustomDate($item->last_refill_date) : null,
                'last_refill_date' => count($item->inventories) > 0 ? formatToCustomDate($item->inventories[0]->last_refill_date) : null,
            ];
        });
        
        if($this->report){
            return [
                'product_list' => $produtList
            ];
        }else{
            return [
                'product_list' => $produtList,
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
} 