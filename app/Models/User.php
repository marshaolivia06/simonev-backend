<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function guru()
    {
        return $this->hasOne(\App\Models\Guru::class, 'id_user');
    }

    public function orangTua()
    {
        return $this->hasOne(\App\Models\OrangTua::class, 'id_user');
    }

    public function pengumuman()
    {
        return $this->hasMany(\App\Models\Pengumuman::class, 'id_user');
    }
}