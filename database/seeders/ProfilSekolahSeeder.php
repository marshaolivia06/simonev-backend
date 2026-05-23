<?php

namespace Database\Seeders;

use App\Models\ProfilSekolah;
use Illuminate\Database\Seeder;

class ProfilSekolahSeeder extends Seeder
{
    public function run(): void
    {
        ProfilSekolah::create([
            'nama_sekolah' => 'TK Pertiwi',
            'email'        => 'admin@tkpertiwi.sch.id',
            'telepon'      => '',
            'alamat'       => '',
        ]);
    }
}