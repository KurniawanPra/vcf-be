<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VcfKelengkapanSupir extends Model
{
    use HasFactory;

    protected $table = 'vcf_kelengkapan_supirs';

    protected $fillable = [
        'vcf_id',
        'item_id',
        'nilai',
        'keterangan'
    ];

    public function vcf()
    {
        return $this->belongsTo(Vcf::class);
    }

    public function item()
    {
        return $this->belongsTo(ItemKelengkapanSupir::class, 'item_id');
    }
}
