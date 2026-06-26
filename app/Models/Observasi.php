<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Observasi extends Model
{
    protected $table = 'observasi';
    protected $primaryKey = 'id_observasi';

    protected $fillable = [
        'id_anak',
        'id_indikator',
        'id_guru',
        'nilai',
        'komentar',
        'keterangan_aspek', // [BARU] JSON per aspek
        'foto',
        'tanggal',
        'semester',
    ];

    /**
     * Cast keterangan_aspek dari JSON string → PHP array otomatis
     * sehingga bisa langsung diakses sebagai array di controller
     */
    protected $casts = [
        'keterangan_aspek' => 'array',
    ];

    // ── Relasi ───────────────────────────────────────────────────

    public function anak()
    {
        return $this->belongsTo(Anak::class, 'id_anak');
    }

    public function indikator()
    {
        return $this->belongsTo(IndikatorPenilaian::class, 'id_indikator')
            ->with('aspek'); // eager load aspek supaya bisa diakses via indikator.aspek
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'id_guru');
    }
}
