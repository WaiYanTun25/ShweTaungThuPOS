<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderDetail extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = ['sale_order_id', 'item_id', 'unit_id', 'discount_amount', 'item_price', 'quantity', 'amount'];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sale_order_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
