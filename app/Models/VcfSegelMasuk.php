<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VcfSegelMasuk extends Model
{
    use HasFactory;

    protected $table = 'vcf_segel_masuks';

    protected $fillable = [
        'vcf_id',
        'jumlah_segel',
        'petugas_id',
        'keterangan'
    ];

    public function vcf()
    {
        return $this->belongsTo(Vcf::class);
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function nomorSegel()
    {
        return $this->hasMany(VcfNomorSegelMasuk::class, 'segel_masuk_id');
    }
}
