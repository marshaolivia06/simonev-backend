<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfilSekolah extends Model
{
    protected $table    = 'profil_sekolah';
    protected $fillable = ['nama_sekolah', 'email', 'telepon', 'alamat'];
}