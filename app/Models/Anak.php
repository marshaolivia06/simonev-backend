<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Anak extends Model
{
    protected $table = 'anak';
    protected $primaryKey = 'id_anak';
    protected $fillable = ['id_kelas', 'id_orangtua', 'nama_anak', 'jenis_kelamin', 'tanggal_lahir'];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'id_kelas');
    }

    public function orangTua()
    {
        return $this->belongsTo(OrangTua::class, 'id_orangtua');
    }

    public function observasi()
    {
        return $this->hasMany(Observasi::class, 'id_anak');
    }
}
