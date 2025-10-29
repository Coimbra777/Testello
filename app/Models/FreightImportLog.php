<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreightImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'freight_table_id',
        'row_number',
        'message',
    ];

    public function freightTable()
    {
        return $this->belongsTo(FreightTable::class);
    }
}
