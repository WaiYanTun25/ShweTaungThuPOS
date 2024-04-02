<?php

namespace App\Traits;

use App\Models\Branch;
use stdClass;
use App\Models\{
   ItemUnitDetail,
   Category,
   Item
};

trait ItemTrait
{
   public function checkItemHasRelatedData($itemId)
   {
    return true;
   }

   public function getItemRelatedData($itemId)
   {
      $relatedUnitData = ItemUnitDetail::where('item_id', $itemId)->with('unit')->get();
      $result = $relatedUnitData->map(function ($item) {
         $unitName = $item->unit->name;
         unset($item->unit); // Remove the 'unit' key
         $item->unit_name = $unitName; // Add 'unit_name' with the unit name
         return collect($item)->except(['reorder_level', 'reorder_period']);
      });

      return $result;
   }

   public function getCategoryName($itemId)
   {
      $getItem = Item::find($itemId);
      return $getItem->category->name;
   }
}
