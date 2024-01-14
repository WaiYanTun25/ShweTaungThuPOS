<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferDetail extends Model
{
    use HasFactory;
    public $timestamps = false;
    
    public function damage()
    {
        return $this->hasOne(Damage::class, 'voucher_no', 'voucher_no');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
