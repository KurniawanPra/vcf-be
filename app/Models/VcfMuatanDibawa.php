<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VcfMuatanDibawa extends Model
{
    use HasFactory;

    protected $table = 'vcf_muatan_dibawas';

    protected $fillable = [
        'vcf_id',
        'item_muatan_id',
        'nilai',
        'keterangan'
    ];

    public function vcf()
    {
        return $this->belongsTo(Vcf::class);
    }

    public function itemMuatan()
    {
        return $this->belongsTo(ItemMuatan::class, 'item_muatan_id');
    }
}
