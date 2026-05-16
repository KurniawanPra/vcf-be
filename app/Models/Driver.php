<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';
    protected $table = 'drivers';

    protected $fillable = [
        'nama_supir',
        'no_sim',
        'jenis_sim',
        'tgl_berlaku_sim',
        'is_active',
        'status'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tgl_berlaku_sim' => 'date',
    ];


    public function vcfs()
    {
        return $this->hasMany(Vcf::class, 'driver_id');
    }
}
