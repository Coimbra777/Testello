<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreightTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'version',
        'file_name',
        'checksum',
        'status',
        'total_rows',
        'total_errors',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function freightRates()
    {
        return $this->hasMany(FreightRate::class);
    }

    public function importLogs()
    {
        return $this->hasMany(FreightImportLog::class);
    }
}
