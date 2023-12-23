<?php

namespace App\Traits;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\TransferDetail;

trait TransactionTrait
{
   public function createTransactionDetail($dataArray, $voucher_no)
   {
      foreach($dataArray as $data) {
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

   public function updatedTransactionDetail($dataArray, $voucher_no)
   {
      // get and delete prev trans details
      $previousTransactionDetail = TransferDetail::where('voucher_no', $voucher_no)->get();
      foreach($previousTransactionDetail as $prevDetail){
         $addInventoryBack = Inventory::where('item_id', $prevDetail->item_id)->where('unit_id', $prevDetail->unit_id)->first();
         $addInventoryBack->increment('quantity', $prevDetail['quantity']);
         $prevDetail->delete();
      }

      foreach($dataArray as $data) {
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

   public function deleteTransactionDetail($voucher_no)
   {
      $transactionDetail = TransferDetail::where('voucher_no', $voucher_no)->get();
      foreach($transactionDetail as $data) {        
         $addInventoryBack = Inventory::where('item_id', $data->item_id)->where('unit_id', $data->unit_id)->first();
         $addInventoryBack->increment('quantity', $data->quantity);
         $data->delete();
      }
      return true;
   }
}
