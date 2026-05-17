<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Violation extends Model
{
    use HasFactory;

    protected $table = 'violations';

    protected $fillable = [
        'driver_id',
        'no_polisi',
        'jenis_pelanggaran',
        'keterangan',
        'tanggal_pelanggaran',
        'created_by',
    ];

    protected $casts = [
        'tanggal_pelanggaran' => 'date',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
