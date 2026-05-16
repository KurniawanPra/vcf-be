<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VcfNomorSegelKeluar extends Model
{
    use HasFactory;

    protected $table = 'vcf_nomor_segel_keluars';

    protected $fillable = [
        'segel_keluar_id',
        'urutan',
        'nomor_segel'
    ];

    public function segelKeluar()
    {
        return $this->belongsTo(VcfSegelKeluar::class, 'segel_keluar_id');
    }
}
