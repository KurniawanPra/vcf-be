<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $table = 'produks';

    protected $fillable = [
        'nama',
        'keterangan',
        'kode',
        'nomor_urut',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function vcfs()
    {
        return $this->hasMany(Vcf::class, 'produk_id');
    }
}
