<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($supplier) {
            // Set the join_date attribute to the current date if it's not provided
            $supplier->join_date = $supplier->join_date ?? now();
        });
    }
}
