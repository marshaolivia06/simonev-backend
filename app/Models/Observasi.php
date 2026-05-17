<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Observasi extends Model
{
    protected $table = 'observasi';
    protected $primaryKey = 'id_observasi';
    protected $fillable = ['id_guru', 'id_anak', 'id_indikator', 'semester', 'tanggal', 'nilai', 'komentar', 'foto'];

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'id_guru');
    }

    public function anak()
    {
        return $this->belongsTo(Anak::class, 'id_anak');
    }

    public function indikator()
    {
        return $this->belongsTo(IndikatorPenilaian::class, 'id_indikator');
    }
}
