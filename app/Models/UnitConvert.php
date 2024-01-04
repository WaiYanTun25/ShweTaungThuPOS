<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\BranchScope;

class UnitConvert extends Model
{
    use HasFactory;
    public $timestamps = false;

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
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
