<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndikatorPenilaian extends Model
{
    protected $table = 'indikator_penilaian';
    protected $primaryKey = 'id_indikator';
    protected $fillable = ['id_aspek', 'nama_indikator', 'nama_kegiatan'];

    public function aspek()
    {
        return $this->belongsTo(AspekPerkembangan::class, 'id_aspek');
    }

    public function observasi()
    {
        return $this->hasMany(Observasi::class, 'id_indikator');
    }
}