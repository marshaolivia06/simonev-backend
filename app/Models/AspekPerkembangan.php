<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AspekPerkembangan extends Model
{
    protected $table = 'aspek_perkembangan';
    protected $primaryKey = 'id_aspek';
    protected $fillable = ['nama_aspek', 'definisi_aspek'];

    public function indikator()
    {
        return $this->hasMany(IndikatorPenilaian::class, 'id_aspek');
    }
}