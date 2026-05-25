<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timbangan extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $table = 'vcf_timbangans';

    protected $fillable = [
        'vcf_id',
        'bruto_from',
        'tara_from',
        'netto_from',
        'bruto',
        'tara',
        'netto',
    ];

    public function vcf()
    {
        return $this->belongsTo(Vcf::class, 'vcf_id');
    }
}
