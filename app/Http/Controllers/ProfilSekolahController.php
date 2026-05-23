<?php
// php artisan make:controller ProfilSekolahController

namespace App\Http\Controllers;

use App\Models\ProfilSekolah;
use Illuminate\Http\Request;

class ProfilSekolahController extends Controller
{
    public function show()
    {
        $profil = ProfilSekolah::first();

        return response()->json([
            'success' => true,
            'data'    => $profil,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'nama_sekolah' => 'required|string',
            'email'        => 'nullable|email',
            'telepon'      => 'nullable|string',
            'alamat'       => 'nullable|string',
        ]);

        $profil = ProfilSekolah::first();

        if (!$profil) {
            $profil = ProfilSekolah::create($request->only([
                'nama_sekolah', 'email', 'telepon', 'alamat'
            ]));
        } else {
            $profil->update($request->only([
                'nama_sekolah', 'email', 'telepon', 'alamat'
            ]));
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil sekolah berhasil diperbarui',
            'data'    => $profil,
        ]);
    }
}