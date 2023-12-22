<?php

namespace App\Traits;

use App\Models\Branch;

trait CustomerTrait
{
   public function checkCustomerHasRelatedData($itemId)
   {
    return true;
   }
}
