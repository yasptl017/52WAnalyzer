<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataArchive extends Model
{
    use HasFactory;

    protected $table = 'data_archives';

    protected $fillable = [
        'trade_date',
        'source',
        'total_52wh',
        'total_vg',
        'status',
        'raw_file_52wh',
        'raw_file_vg',
        'raw_file_bhavcopy',
        'notes',
    ];

    protected $casts = [
        'trade_date' => 'date',
        'total_52wh' => 'integer',
        'total_vg' => 'integer',
    ];
}
