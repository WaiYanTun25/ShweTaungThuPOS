<?php

namespace App\Observers;

use App\Models\Inventory;
use App\Models\Transfer;
use App\Models\TransferDetail;
use Exception;
use Illuminate\Http\Request;

class TransferObserver
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function creating(Transfer $transfer): void
    {
        try{
            // info($this->request->item_detail);
            foreach($this->request->item_detail as $detail)
            {
                $deductFromInventory = Inventory::where('item_id', $detail['item_id'])
                ->where('unit_id', $detail['unit_id'])
                ->where('branch_id', $this->request->from_branch_id)
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
    /**
     * Handle the Issue "created" event.
     */
    public function created(Transfer $transfer): void
    {

    }

    /**
     * Handle the Issue "updating" event.
     */
    public function updating(Transfer $transfer): void
    {
        try{
            $fromBranchId = $transfer->from_branch_id;
            foreach($this->request->item_detail as $detail)
            {
                $deductFromInventory = Inventory::where('item_id', $detail['item_id'])
                ->where('unit_id', $detail['unit_id'])
                ->where('branch_id', $fromBranchId)
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

    /**
     * Handle the Issue "updated" event.
     */
    public function updated(Transfer $transfer): void
    {
        //
    }

     /**
     * Handle the Issue "deleting" event.
     */
    public function deleting(Transfer $transfer): void
    {
        //
    }

    /**
     * Handle the Issue "deleted" event.
     */
    public function deleted(Transfer $transfer): void
    {
        //
    }

    /**
     * Handle the Issue "restored" event.
     */
    public function restored(Transfer $transfer): void
    {
        //
    }

    /**
     * Handle the Issue "force deleted" event.
     */
    public function forceDeleted(Transfer $transfer): void
    {
        //
    }
}
