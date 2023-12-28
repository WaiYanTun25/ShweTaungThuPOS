<?php

namespace App\Observers;

use App\Models\Inventory;
use App\Models\Issue;
use App\Models\TransferDetail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransferObserver
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function creating(Issue $issue): void
    {
        try{
            // info($this->request->item_detail);
            foreach($this->request->item_details as $detail)
            {
                $deductFromInventory = Inventory::where('item_id', $detail['item_id'])
                ->where('unit_id', $detail['unit_id'])
                ->where('branch_id', Auth::user()->branch_id)
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
    public function created(Issue $transfer): void
    {

    }

    /**
     * Handle the Issue "updating" event.
     */
    public function updating(Issue $transfer): void
    {
        try{
            $fromBranchId = $transfer->from_branch_id;
            foreach($this->request->item_details as $detail)
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
    public function updated(Issue $transfer): void
    {
        //
    }

     /**
     * Handle the Issue "deleting" event.
     */
    public function deleting(Issue $transfer): void
    {
        //
    }

    /**
     * Handle the Issue "deleted" event.
     */
    public function deleted(Issue $transfer): void
    {
        //
    }

    /**
     * Handle the Issue "restored" event.
     */
    public function restored(Issue $transfer): void
    {
        //
    }

    /**
     * Handle the Issue "force deleted" event.
     */
    public function forceDeleted(Issue $transfer): void
    {
        //
    }
}
