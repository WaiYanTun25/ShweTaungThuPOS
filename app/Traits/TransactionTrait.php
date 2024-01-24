<?php

namespace App\Traits;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\ItemUnitDetail;
use App\Models\Receive;
use App\Models\TransferDetail;
use Error;
use Exception;
use Illuminate\Support\Facades\Auth;

trait TransactionTrait
{
   public function createTransactionDetail($dataArray, $voucher_no)
   {
      $receiveDetail = [];
      foreach ($dataArray as $data) {
         $createdTransactionDetail = new TransferDetail();
         $createdTransactionDetail->voucher_no = $voucher_no;
         $createdTransactionDetail->item_id = $data['item_id'];
         $createdTransactionDetail->unit_id = $data['unit_id'];
         $createdTransactionDetail->quantity = $data['quantity'];
         $createdTransactionDetail->save();
         $receiveDetail[] = $createdTransactionDetail;
      }

      return $receiveDetail;
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

      $data = [];
      foreach ($dataArray as $data) {
         $createdTransactionDetail = new TransferDetail();
         $createdTransactionDetail->voucher_no = $voucher_no;
         $createdTransactionDetail->item_id = $data['item_id'];
         $createdTransactionDetail->unit_id = $data['unit_id'];
         $createdTransactionDetail->quantity = $data['quantity'];
         $createdTransactionDetail->save();
         $data[] = $createdTransactionDetail;
      }

      return $data;
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
   public function deductItemFromBranch($item_details, $branchId , $sales = false)
   {
      try{
         // info($this->request->item_detail);
         foreach($item_details as $index=>$detail)
         {
             $deductFromInventory = Inventory::where('item_id', $detail['item_id'])
             ->where('unit_id', $detail['unit_id'])
             ->where('branch_id', $branchId)
             ->first();

            if ($deductFromInventory && $deductFromInventory->quantity >= $detail['quantity']) {
               // Perform the decrement
               $deductFromInventory->decrement('quantity', $detail['quantity']);
            } else if ($sales) 
            {
               $deductFromInventory->quantity = $deductFromInventory->quantity - $detail['quantity'];
               $deductFromInventory->save();
            }
            else {
                  // Handle insufficient quantity or other logic
                  // You might want to throw an exception, log a message, or take appropriate action
                  throw new \Exception('Insufficient quantity to deduct.');
                  // throw new \Exception("Insufficient Quantity for {$index}.item_id: {$detail['item_id']}, unit_id: {$detail['unit_id']}");
            }
         }
     }catch(Exception $e)
     {
         throw new \Exception($e->getMessage());
     }
   }

   public function addItemtoBranch($requestDetails , $voucher_no = null)
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
                $createInventory->branch_id = Auth::user()->branch_id;
                $createInventory->quantity = $detail['quantity'];
                $createInventory->save();
            }
        }
      }catch(Exception $e){
         throw new Error($e->getMessage());
      }
   }

   public function updatedDamageDetail($currDetails, $voucher_no, $branchId)
   {
      $deletePrevDetailAndAddInventory = $this->deleteDamageDetailAndIncInventory($voucher_no, $branchId);
      $data = [];
      if($deletePrevDetailAndAddInventory)
      {
         foreach ($currDetails as $data) {
            $createdTransactionDetail = new TransferDetail();
            $createdTransactionDetail->voucher_no = $voucher_no;
            $createdTransactionDetail->item_id = $data['item_id'];
            $createdTransactionDetail->unit_id = $data['unit_id'];
            $createdTransactionDetail->quantity = $data['quantity'];
            $createdTransactionDetail->save();
            $data[] = $createdTransactionDetail;

            $deductInventory = Inventory::where('item_id', $data['item_id'])->where('unit_id', $data['unit_id'])->where('branch_id', $branchId)->first();
            $deductInventory->decrement('quantity', $data['quantity']);
         }
      }
      return $data;
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

   // receive create
   // public function createOrUpdateReceive($request , $update = false , $receive = null)
   // {
   //    if($update){
   //       $createReceive = $receive;
   //    }else{
   //       $createReceive = new Receive();
   //    }
   //    $branchId = $request->from_branch_id;
   //    $createReceive->from_branch_id = $branchId;
   //    $createReceive->save();

   //    return $createReceive;
   // }
}
