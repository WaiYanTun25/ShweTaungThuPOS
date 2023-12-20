<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        // Creating event to generate and set the item_code
        static::creating(function ($item) {
            $item->item_code = static::generateItemCode();
        });
    }

    protected static function generateItemCode()
    {
        // Get the last item from the database
        $lastItem = static::latest('id')->first();

        if (!$lastItem) {
            return 'p-000001';
        }

        return ++$lastItem->item_code;
    }

    public function ItemUnitDetails()
    {
        return $this->hasmany(ItemUnitDetail::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
