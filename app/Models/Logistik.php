<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logistik extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $table = 'logistiks';

    protected $fillable = [
        'nama_logistik',
        'nama',
        'kode',
        'is_active'
    ];

    public function vcfs()
    {
        return $this->hasMany(Vcf::class, 'logistik_id');
    }
}
