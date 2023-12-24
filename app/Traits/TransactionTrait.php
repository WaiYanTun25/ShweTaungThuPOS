<?php

namespace App\Traits;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\TransferDetail;
use Exception;

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
         $createdTransactionDetail->remark = $data['remark'];
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
         $createdTransactionDetail->remark = $data['remark'];
         $createdTransactionDetail->save();
      }

      return true;
   }

   public function deleteTransactionDetail($voucher_no, $branchId)
   {
      $transactionDetail = TransferDetail::where('voucher_no', $voucher_no)->get();
      foreach ($transactionDetail as $data) {
         $addInventoryBack = Inventory::where('item_id', $data->item_id)->where('unit_id', $data->unit_id)->first();
         $addInventoryBack->increment('quantity', $data->quantity);
         $data->delete();
      }
      return true;
   }

   // Reveive Function
   public function addOrCreateItemToInventories($data)
   {
      foreach ($data->transfer_details as $detail) {
         $checkInventory = Inventory::where('item_id', $detail->item_id)->where('unit_id', $detail->unit_id)
         ->where('branch_id', $data->to_branch_id)->first();

         if($checkInventory)
         {
            $checkInventory->increment('quantity', $detail->quantity);
         }else{
            $createInventory = new Inventory();
            $createInventory->branch_id = $data->to_branch_id;
            $createInventory->item_id = $detail->item_id;
            $createInventory->unit_id = $detail->unit_id;
            $createInventory->quantity = $detail->quantity;
            $createInventory->save();
         }
      }

      return true;
   }

   // damage functions
   public function deductDamageItemFromBranch($item_detail, $branchId)
   {
      try{
         // info($this->request->item_detail);
         foreach($item_detail as $detail)
         {
             $deductFromInventory = Inventory::where('item_id', $detail['item_id'])
             ->where('unit_id', $detail['unit_id'])
             ->where('branch_id', $branchId)
             ->first();
             // $deductQuantity = min($deductFromInventory->quantity, $detail['quantity']);
             if($deductFromInventory)
             {
                 $deductFromInventory->decrement('quantity', $detail['quantity']);
             }else{
                 throw new Exception('Failed to deduct quantity from inventories');
             }
         }
     }catch(Exception $e)
     {
         info($e->getMessage());
         throw new Exception('Failed to deduct quantity from inventories');
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
            $createdTransactionDetail->remark = $data['remark'];
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
