<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vcf extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $table = 'vcfs';

    protected $fillable = [
        'nomor_urut',
        'tanggal',
        'produk',
        'tipe_kegiatan',
        'asal_tujuan',
        'jenis_kendaraan_id',
        'no_polisi',
        'tipe_kendaraan',
        'tahun_kendaraan',
        'transporter_id',
        'driver_id',
        'jam_masuk',
        'status',
        'keterangan',
        'catatan',
        'created_by'
    ];

    public function jenisKendaraan()
    {
        return $this->belongsTo(JenisKendaraan::class, 'jenis_kendaraan_id');
    }

    public function transporter()
    {
        return $this->belongsTo(Transporter::class,'transporter_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class,'driver_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function kelengkapanSupir()
    {
        return $this->hasMany(VcfKelengkapanSupir::class);
    }

    public function muatanDibawa()
    {
        return $this->hasMany(VcfMuatanDibawa::class);
    }

    public function muatanDiisi()
    {
        return $this->hasMany(VcfMuatanDiisi::class);
    }

    public function pemeriksaanMasuk()
    {
        return $this->hasMany(VcfPemeriksaanMasuk::class);
    }

    public function bebanTambahanMasuk()
    {
        return $this->hasOne(VcfBebanTambahanMasuk::class);
    }

    public function segelMasuk()
    {
        return $this->hasOne(VcfSegelMasuk::class);
    }

    public function pemeriksaanKeluar()
    {
        return $this->hasMany(VcfPemeriksaanKeluar::class);
    }

    public function bebanTambahanKeluar()
    {
        return $this->hasOne(VcfBebanTambahanKeluar::class);
    }

    public function segelKeluar()
    {
        return $this->hasOne(VcfSegelKeluar::class);
    }

    public function vcfKeluar()
    {
        return $this->hasOne(VcfKeluar::class);
    }

    public function timbangan()
    {
        return $this->hasOne(Timbangan::class);
    }
}
