<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\BranchScope;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

use Spatie\Activitylog\Models\Activity;

class UnitConvert extends Model
{
    use HasFactory, LogsActivity;
    public $timestamps = false;
    protected $fillable = ['branch_id', 'item_id', 'convert_date'];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        $logOptions = LogOptions::defaults()
            ->setDescriptionForEvent(function (string $eventName) {
                $userName = "{userName}";
                return "{$userName} {$eventName} the convert of {$this->item->item_name}";
            });


        $logOptions->logName = 'UNIT_CONVERT';

        return $logOptions;
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new BranchScope());

        static::creating(function ($model) {
            // for convert_date
            if (!$model->convert_date) {
                $model->convert_date = now();
            }
        });
    }

    public function convertDetail()
    {
        return $this->hasOne(UnitConvertDetail::class, 'unit_convert_id', 'id');
    }
    
    /**
     * Retrieve the associated item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
   
}
