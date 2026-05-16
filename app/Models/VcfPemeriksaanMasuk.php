<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VcfPemeriksaanMasuk extends Model
{
    use HasFactory;

    protected $table = 'vcf_pemeriksaan_masuks';

    protected $fillable = [
        'vcf_id',
        'item_id',
        'nilai',
        'keterangan',
        'petugas_id',
        'waktu_input'
    ];

    public function vcf()
    {
        return $this->belongsTo(Vcf::class);
    }

    public function item()
    {
        return $this->belongsTo(ItemPemeriksaanMasuk::class, 'item_id');
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}
