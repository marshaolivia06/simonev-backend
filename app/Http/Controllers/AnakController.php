<?php

namespace App\Http\Controllers;

use App\Models\Anak;
use Illuminate\Http\Request;

class AnakController extends Controller
{
    /**
     * Ambil semua data anak.
     *
     * Mengembalikan daftar seluruh anak yang orang tuanya sudah diverifikasi admin,
     * bisa difilter berdasarkan id_kelas.
     */

    public function index(Request $request)
    {
        $query = Anak::with('kelas', 'orangTua.user')
            ->whereHas('orangTua.user', function ($q) {
                $q->where('status', 'approved');
            });

        if ($request->has('id_kelas')) {
            $query->where('id_kelas', $request->id_kelas);
        }

        $data = $query->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

     /**
     * Tambah data anak baru.
     *
     * Menyimpan data anak baru ke database beserta relasi kelas dan orang tua.
     */

    public function store(Request $request)
    {
        $request->validate([
            'id_kelas'      => 'required|exists:kelas,id_kelas',
            'nama_anak'     => 'required|string',
            'jenis_kelamin' => 'required|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'id_orangtua'   => 'nullable|exists:orang_tua,id_orangtua',
        ]);

        $data = Anak::create($request->all());
        return response()->json(['success' => true, 'data' => $data], 201);
    }

    /**
     * Ambil detail data anak.
     *
     * Mengembalikan data detail anak berdasarkan ID beserta relasi kelas, orang tua, dan observasi.
     */

    public function show($id)
    {
        $data = Anak::with('kelas', 'orangTua.user', 'observasi')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Update data anak.
     *
     * Mengubah data anak berdasarkan ID.
     */

    public function update(Request $request, $id)
    {
        $data = Anak::findOrFail($id);
        $data->update($request->all());
        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Hapus data anak.
     *
     * Menghapus data anak berdasarkan ID dari database.
     */

    public function destroy($id)
    {
        Anak::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }
}