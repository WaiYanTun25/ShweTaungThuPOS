<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

use Spatie\Activitylog\Models\Activity;


class Branch extends Model
{
    use HasFactory, LogsActivity;
    public $timestamps = false;

    protected $fillable = ['name','phone_number', 'employee_number', 'address'];

    public function getActivitylogOptions(): LogOptions
    {
        $logOptions = LogOptions::defaults()
            ->setDescriptionForEvent(function (string $eventName) {
                $userName = "{userName}";
                return "{$userName} {$eventName} the branch (branch_name {$this->name})";
            });


        $logOptions->logName = 'BRANCH';

        return $logOptions;
    }

    protected static function boot()
    {
        parent::boot();
        // Define a callback function to be executed when a new model is being created
        static::creating(function ($model) {
            // Generate voucher_no if it's not already set
            if (!$model->branch_code) {
                $model->branch_code = $model->generateBranchCode();
            }
        });
    }

    private function generateBranchCode()
    {
        // Get the last voucher number without the branch scope
        $lastVoucherNo = static::where('branch_code', 'like', 'BR-%')->max('branch_code');

        // Generate a new voucher number based on the last voucher number
        if ($lastVoucherNo) {
            $voucherNo = ++$lastVoucherNo;
        } else {
            // Get the current count of existing records and increment it
            $count = static::count() + 1;

            // Generate a formatted voucher number with leading zeros
            $voucherNo = "BR-" . str_pad($count, 10, '0', STR_PAD_LEFT);
        }

        return $voucherNo;
    }
}
