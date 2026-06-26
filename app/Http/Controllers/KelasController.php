<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    /**
     * Ambil semua data kelas.
     *
     * Mengembalikan daftar kelas yang tersedia.
     * Data yang dikembalikan disesuaikan berdasarkan role pengguna yang sedang login.
     */

    public function index(Request $request)
{
    $user = $request->user();

    // ← TAMBAH INI: akses publik (saat register, belum login)
    if (!$user) {
        $data = Kelas::orderBy('nama_kelas', 'asc')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    // Admin → ambil semua kelas
    if ($user->role === 'admin') {
        $data = Kelas::orderBy('nama_kelas', 'asc')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    // Guru → filter by wali_kelas (nama guru)
    if ($user->role === 'guru') {
        $guru = $user->guru;
        if ($guru) {
            $data = Kelas::where('wali_kelas', $guru->nama_guru)
                         ->orderBy('nama_kelas', 'asc')
                         ->get();
            return response()->json(['success' => true, 'data' => $data]);
        }
    }

    // Fallback
    return response()->json(['success' => true, 'data' => []]);
}

    /**
     * Tambah kelas baru.
     *
     * Menyimpan data kelas baru ke database beserta wali kelas dan tahun ajaran.
     */

    public function store(Request $request)
    {
        $request->validate([
            'nama_kelas'   => 'required|string',
            'wali_kelas'   => 'nullable|string',
            'tahun_ajaran' => 'required|string',
        ]);

        $data = Kelas::create($request->all());
        return response()->json(['success' => true, 'data' => $data], 201);
    }

    /**
     * Ambil detail kelas.
     *
     * Mengembalikan data detail kelas berdasarkan ID.
     */

    public function show($id)
    {
        $data = Kelas::findOrFail($id);
        return response()->json(['success' => true, 'data' => $data]);
    }

     /**
     * Update data kelas.
     *
     * Mengubah data kelas berdasarkan ID.
     */

    public function update(Request $request, $id)
    {
        $data = Kelas::findOrFail($id);
        $data->update($request->only(['nama_kelas', 'wali_kelas', 'tahun_ajaran']));
        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Hapus data kelas.
     *
     * Menghapus data kelas berdasarkan ID dari database.
     */

    public function destroy($id)
    {
        Kelas::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }
}