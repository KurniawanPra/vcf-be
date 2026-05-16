<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VcfBebanTambahanKeluar extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';
    protected $table = 'vcf_beban_tambahan_keluars';

    protected $fillable = [
        'vcf_id',
        'jenis_beban'
    ];

    public function vcf(){
        return $this->belongsTo(Vcf::class, 'vcf_id');
    }
}
