<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    protected $table = 'pengumuman';
    protected $primaryKey = 'id_pengumuman';
    protected $fillable = ['id_user', 'judul_pengumuman', 'isi_pengumuman', 'tanggal'];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
