<?php

namespace App\Http\Controllers;

use App\Models\ProfilSekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'nama_sekolah'        => 'required|string',
            'email'               => 'nullable|email',
            'telepon'             => 'nullable|string',
            'alamat'              => 'nullable|string',
            'nama_kepala_sekolah' => 'nullable|string',
            'nip_kepala_sekolah'  => 'nullable|string',
            'foto_ttd_ks'         => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $profil = ProfilSekolah::first();

        $data = $request->only([
            'nama_sekolah', 'email', 'telepon', 'alamat',
            'nama_kepala_sekolah', 'nip_kepala_sekolah',
        ]);

        // Handle upload foto TTD kepala sekolah
        if ($request->hasFile('foto_ttd_ks')) {
            // Hapus foto lama kalau ada
            if ($profil && $profil->foto_ttd_ks) {
                Storage::disk('public')->delete($profil->foto_ttd_ks);
            }
            $data['foto_ttd_ks'] = $request->file('foto_ttd_ks')->store('ttd', 'public');
        }

        if (!$profil) {
            $profil = ProfilSekolah::create($data);
        } else {
            $profil->update($data);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil sekolah berhasil diperbarui',
            'data'    => $profil,
        ]);
    }
}