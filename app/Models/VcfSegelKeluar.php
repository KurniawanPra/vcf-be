<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VcfSegelKeluar extends Model
{
    use HasFactory;

    protected $table = 'vcf_segel_keluars';

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
        return $this->hasMany(VcfNomorSegelKeluar::class, 'segel_keluar_id');
    }
}
