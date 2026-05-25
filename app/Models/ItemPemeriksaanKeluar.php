<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemPemeriksaanKeluar extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $table = 'item_pemeriksaan_keluars';

    protected $fillable = [
        'nama_item',
        'kode',
        'tipe_jawaban',
        'has_detail',
        'keterangan_detail',
        'tampil_pada',
        'urutan',
        'is_active'
    ];

    public function vcfs()
    {
        return $this->hasMany(VcfPemeriksaanKeluar::class, 'item_id');
    }
}