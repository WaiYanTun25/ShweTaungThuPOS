<?php

namespace App\Traits;

use App\Models\Branch;

trait CustomerTrait
{
   public function checkCustomerHasRelatedData($customer)
   {
      if($customer->sales()->exists() || $customer->customerPayments()->exists()){
         return true;
      }
      return false;
   }
}
