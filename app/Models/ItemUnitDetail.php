<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemUnitDetail extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'item_id', 'item_id')
        ->where('unit_id', $this->unit_id);
    }
}
