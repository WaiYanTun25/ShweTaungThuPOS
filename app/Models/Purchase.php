<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\BranchScope;

class Purchase extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Add a global scope to filter by branch
        static::addGlobalScope(new BranchScope());

        // Define a callback function to be executed when a new model is being created
        static::creating(function ($model) {
            // Generate voucher_no if it's not already set
            if (!$model->voucher_no) {
                $model->voucher_no = $model->generateVoucherNo();
            }

            // Set purchase_date to current date if it's not already set
            if (!$model->purchase_date) {
                $model->purchase_date = now();
            }
        });
    }

    /**
     * Generates a voucher number for the purchase.
     *
     * @return string The generated voucher number.
     */
    private function generateVoucherNo()
    {
        // Get the last voucher number without the branch scope
        $lastVoucherNo = static::withoutGlobalScope(BranchScope::class)->where('voucher_no', 'like', 'PUR%')->max('voucher_no');

        // Generate a new voucher number based on the last voucher number
        if ($lastVoucherNo) {
            $voucherNo = ++$lastVoucherNo;
        } else {
            // Get the current count of existing records and increment it
            $count = static::count() + 1;

            // Generate a formatted voucher number with leading zeros
            $voucherNo = "PUR-" . str_pad($count, 10, '0', STR_PAD_LEFT);
        }

        return $voucherNo;
    }
}