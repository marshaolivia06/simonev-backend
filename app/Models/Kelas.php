<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    protected $table = 'kelas';
    protected $primaryKey = 'id_kelas';
    protected $fillable = ['id_guru', 'nama_kelas', 'tahun_ajaran'];

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'id_guru');
    }

    public function anak()
    {
        return $this->hasMany(Anak::class, 'id_kelas');
    }
}
