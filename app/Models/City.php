<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class City extends Model
{
    use HasFactory, LogsActivity;
    public $timestamps = false;
    protected $fillable = ['name'];

    public function getActivitylogOptions(): LogOptions
    {
        $logOptions = LogOptions::defaults()
            ->setDescriptionForEvent(function (string $eventName) {
                $userName = "{userName}";
                return "{$userName} {$eventName} the City ({$this->name})";
            });


        $logOptions->logName = 'CITY';

        return $logOptions;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // for transaction_date
            if (!$model->created_date) {
                $model->created_date = now();
            }
        });
    }

    public function townships()
    {
        return $this->hasMany(Township::class);
    }
}
