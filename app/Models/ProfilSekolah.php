<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfilSekolah extends Model
{
    protected $table    = 'profil_sekolah';
    protected $fillable = [
        'nama_sekolah', 'email', 'telepon', 'alamat',
        'nama_kepala_sekolah', 'nip_kepala_sekolah', 'foto_ttd_ks',
    ];
}