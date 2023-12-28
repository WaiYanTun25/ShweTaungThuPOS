<?php

namespace App\Traits;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\ItemUnitDetail;
use App\Models\TransferDetail;
use Exception;
use Illuminate\Support\Facades\Auth;

trait TransactionTrait
{
   public function createTransactionDetail($dataArray, $voucher_no)
   {
      foreach ($dataArray as $data) {
         $createdTransactionDetail = new TransferDetail();
         $createdTransactionDetail->voucher_no = $voucher_no;
         $createdTransactionDetail->item_id = $data['item_id'];
         $createdTransactionDetail->unit_id = $data['unit_id'];
         $createdTransactionDetail->quantity = $data['quantity'];
         $createdTransactionDetail->save();
      }

      return true;
   }

   public function updatedTransactionDetail($dataArray, $voucher_no, $branchId)
   {
      // get and delete prev trans details
      $previousTransactionDetail = TransferDetail::where('voucher_no', $voucher_no)->get();
      foreach ($previousTransactionDetail as $prevDetail) {
         $addInventoryBack = Inventory::where('item_id', $prevDetail->item_id)->where('unit_id', $prevDetail->unit_id)->where('branch_id', $branchId)->first();
         $addInventoryBack->increment('quantity', $prevDetail['quantity']);
         $prevDetail->delete();
      }

      foreach ($dataArray as $data) {
         $createdTransactionDetail = new TransferDetail();
         $createdTransactionDetail->voucher_no = $voucher_no;
         $createdTransactionDetail->item_id = $data['item_id'];
         $createdTransactionDetail->unit_id = $data['unit_id'];
         $createdTransactionDetail->quantity = $data['quantity'];
         $createdTransactionDetail->save();
      }

      return true;
   }

   public function deleteTransactionDetail($voucher_no, $branchId)
   {
      $transactionDetail = TransferDetail::where('voucher_no', $voucher_no)->get();
      foreach ($transactionDetail as $data) {
         $addInventoryBack = Inventory::where('item_id', $data->item_id)->where('unit_id', $data->unit_id)->where('branch_id', $branchId)->first();
         $addInventoryBack->increment('quantity', $data->quantity);
         $data->delete();
      }
      return true;
   }

   // Reveive Function
   

   // damage functions
   public function deductItemFromBranch($item_details, $branchId)
   {
      try{
         // info($this->request->item_detail);
         foreach($item_details as $detail)
         {
             $deductFromInventory = Inventory::where('item_id', $detail['item_id'])
             ->where('unit_id', $detail['unit_id'])
             ->where('branch_id', $branchId)
             ->first();
             // $deductQuantity = min($deductFromInventory->quantity, $detail['quantity']);
            //  if($deductFromInventory)
            //  {
            //      $deductFromInventory->decrement('quantity', $detail['quantity']);
            //  }else{
            //      throw new Exception('Failed to deduct quantity from inventories');
            //  }

             if ($deductFromInventory && $deductFromInventory->quantity >= $detail['quantity']) {
               // Perform the decrement
               $deductFromInventory->decrement('quantity', $detail['quantity']);
            } else {
                  // Handle insufficient quantity or other logic
                  // You might want to throw an exception, log a message, or take appropriate action
                  throw new \Exception('Insufficient quantity to deduct.');
            }
         }
     }catch(Exception $e)
     {
         info($e->getMessage());
         throw new \Exception('Insufficient quantity to deduct.');
     }
   }

   public function addItemtoBranch($requestDetails , $voucher_no)
   {
      try{
         foreach ($requestDetails as $detail) {
            $addQtyToInventory = Inventory::where('item_id', $detail['item_id'])
                ->where('unit_id', $detail['unit_id'])
                ->where('branch_id', Auth::user()->branch_id)
                ->first();

            if ($addQtyToInventory) {
                $addQtyToInventory->increment('quantity', $detail['quantity']);
            } else {
                $createInventory = new Inventory();
                $createInventory->item_id = $detail['item_id'];
                $createInventory->unit_id = $detail['unit_id'];
                $createInventory->quantity = $detail['quantity'];
                $createInventory->save();
            }
        }
      }catch(Exception $e){

      }
   }

   public function updatedDamageDetail($currDetails, $voucher_no, $branchId)
   {
      $deletePrevDetailAndAddInventory = $this->deleteDamageDetailAndIncInventory($voucher_no, $branchId);
      if($deletePrevDetailAndAddInventory)
      {
         foreach ($currDetails as $data) {
            $createdTransactionDetail = new TransferDetail();
            $createdTransactionDetail->voucher_no = $voucher_no;
            $createdTransactionDetail->item_id = $data['item_id'];
            $createdTransactionDetail->unit_id = $data['unit_id'];
            $createdTransactionDetail->quantity = $data['quantity'];
            $createdTransactionDetail->save();

            $deductInventory = Inventory::where('item_id', $data['item_id'])->where('unit_id', $data['unit_id'])->where('branch_id', $branchId)->first();
            $deductInventory->decrement('quantity', $data['quantity']);
         }
      }
   }

   public function deleteDamageDetailAndIncInventory($voucher_no, $branchId)
   {
      $previousTransactionDetail = TransferDetail::where('voucher_no', $voucher_no)->get();
      foreach ($previousTransactionDetail as $prevDetail) {
         $addInventoryBack = Inventory::where('item_id', $prevDetail->item_id)->where('unit_id', $prevDetail->unit_id)->where('branch_id', $branchId)->first();
         $addInventoryBack->increment('quantity', $prevDetail['quantity']);
         $prevDetail->delete();
      }
      return true;
   }
}
