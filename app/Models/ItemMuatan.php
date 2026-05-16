<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemMuatan extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $table = 'item_muatans';

    protected $fillable = [
        'nama_item',
        'jenis',
        'keterangan',
        'urutan',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function muatanDibawa()
    {
        return $this->hasMany(VcfMuatanDibawa::class, 'item_muatan_id');
    }

    public function muatanDiisi()
    {
        return $this->hasMany(VcfMuatanDiisi::class, 'item_muatan_id');
    }

    // Support for existing controller check
    public function vcfs()
    {
        return $this->hasMany(VcfMuatanDibawa::class, 'item_muatan_id');
    }
}
