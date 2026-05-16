<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VcfMuatanDiisi extends Model
{
    use HasFactory;

    protected $table = 'vcf_muatan_diisis';

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
