<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class SalesTarget extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['target_type', 'amount', 'target_period'];

    public function getActivitylogOptions(): LogOptions
    {
        $logOptions = LogOptions::defaults()
            ->setDescriptionForEvent(function (string $eventName) {
                $userName = "{userName}";
                return "{$userName} {$eventName} the Sales Target";
            });

        $logOptions->logName = 'SALES_TARGET';

        return $logOptions;
    }
}
