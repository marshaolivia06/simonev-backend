<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    protected $table = 'guru';
    protected $primaryKey = 'id_guru';
    protected $fillable = [
    'id_user', 'nik', 'nama_guru', 'no_telp', 'alamat',
    'jenis_kelamin', 'tanggal_lahir', 'nip', 
    'nama_lembaga', 'jabatan', 'surat_tugas'
];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function kelas()
    {
        return $this->hasMany(Kelas::class, 'id_guru');
    }

    public function observasi()
    {
        return $this->hasMany(Observasi::class, 'id_guru');
    }
}
