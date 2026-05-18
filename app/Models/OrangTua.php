<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrangTua extends Model
{
    protected $table = 'orang_tua';
    protected $primaryKey = 'id_orangtua';
    protected $fillable = [
    'id_user', 'nik', 'nama_orangtua', 'no_telp', 
    'alamat', 'pekerjaan', 'hubungan', 'nama_anak', 
    'kelas_anak', 'foto_ktp'
];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function anak()
    {
        return $this->hasMany(Anak::class, 'id_orangtua');
    }
}
