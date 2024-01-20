<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;
    public $timestamps = false;

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
}
