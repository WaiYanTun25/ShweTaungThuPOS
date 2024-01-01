<?php

namespace App\Observers;

use App\Models\Inventory;
use App\Models\Issue;
use App\Models\Receive;
use App\Models\TransferDetail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceiveObserver
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function creating(Receive $receive): void
    {
        try {
            // info($this->request->item_detail);
            foreach ($this->request->item_details as $detail) {
                $addQtyFromInventory = Inventory::where('item_id', $detail['item_id'])
                    ->where('unit_id', $detail['unit_id'])
                    ->where('branch_id', Auth::user()->branch_id)
                    ->first();
                // $deductQuantity = min($deductFromInventory->quantity, $detail['quantity']);
                if ($addQtyFromInventory) {
                    $addQtyFromInventory->increment('quantity', $detail['quantity']);
                } else {
                    $createInventory = new Inventory();
                    $createInventory->branch_id = Auth::user()->branch_id;
                    $createInventory->item_id = $detail['item_id'];
                    $createInventory->unit_id = $detail['unit_id'];
                    $createInventory->quantity = $detail['quantity'];
                    $createInventory->save();
                }
            }
        } catch (Exception $e) {
            info($e->getMessage());
            throw new Exception('Failed to deduct quantity from inventories');
        }
    }
    /**
     * Handle the Issue "created" event.
     */
    public function created(Receive $transfer): void
    {
    }


    /**
     * Handle the Receive "updated" event.
     */
    public function updated(Receive $receive): void
    {
        //
    }

    /**
     * Handle the Receive "deleting" event.
     */
    public function deleting(Receive $receive): void
    {
        //
    }

    /**
     * Handle the Receive "deleted" event.
     */
    public function deleted(Receive $receive): void
    {
        //
    }

    /**
     * Handle the Receive "restored" event.
     */
    public function restored(Receive $receive): void
    {
        //
    }

    /**
     * Handle the Receive "force deleted" event.
     */
    public function forceDeleted(Receive $receive): void
    {
        //
    }
}
