<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisKendaraan extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $table = 'jenis_kendaraans';

    protected $fillable = [
        'nama',
        'kode',
        'urutan',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function vcfs()
    {
        return $this->hasMany(Vcf::class, 'jenis_kendaraan_id');
    }
}
