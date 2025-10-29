<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreightRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'freight_table_id',
        'min_weight',
        'max_weight',
        'price',
    ];

    protected $casts = [
        'min_weight' => 'float',
        'max_weight' => 'float',
        'price' => 'float',
    ];

    public function freightTable()
    {
        return $this->belongsTo(FreightTable::class);
    }
}
