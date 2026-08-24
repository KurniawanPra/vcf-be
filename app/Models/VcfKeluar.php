<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VcfKeluar extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';
    protected $table = 'vcf_keluars';

    protected $fillable = [
        'vcf_id',
        'jam_keluar',
        'emergency_respon_kontak',
        'petugas_id',
        'waktu_input',
        'keterangan'
    ];

    public function vcf(){
        return $this->belongsTo(Vcf::class, 'vcf_id');
    }

    public function petugas(){
        return $this->belongsTo(User::class, 'petugas_id');
    }
}

