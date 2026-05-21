<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timbangan extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $table = 'timbangans';

    protected $fillable = [
        'vcf_id',
        'bruto_from',
        'bruto',
        'tara_from',
        'tara',
        'netto',
    ];

    public function vcf()
    {
        return $this->belongsTo(Vcf::class, 'vcf_id');
    }
}
