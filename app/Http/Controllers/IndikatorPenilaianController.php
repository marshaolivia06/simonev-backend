<?php
namespace App\Http\Controllers;

use App\Models\IndikatorPenilaian;
use Illuminate\Http\Request;

class IndikatorPenilaianController extends Controller
{
    /**
     * Ambil semua indikator penilaian.
     *
     * Mengembalikan daftar seluruh indikator penilaian beserta aspek perkembangannya.
     */

    public function index()
    {
        $data = IndikatorPenilaian::with('aspek')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Tambah indikator penilaian baru.
     *
     * Menyimpan data indikator penilaian baru ke database berdasarkan aspek perkembangan.
     */

    public function store(Request $request)
    {
        $request->validate([
            'id_aspek'      => 'required|exists:aspek_perkembangan,id_aspek',
            'nama_indikator'=> 'required|string',
            'nama_kegiatan' => 'nullable|string',
        ]);

        $data = IndikatorPenilaian::create($request->all());
        return response()->json(['success' => true, 'data' => $data], 201);
    }

    /**
     * Ambil detail indikator penilaian.
     *
     * Mengembalikan data detail indikator penilaian berdasarkan ID beserta aspek perkembangannya.
     */

    public function show($id)
    {
        $data = IndikatorPenilaian::with('aspek')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Update indikator penilaian.
     *
     * Mengubah data indikator penilaian berdasarkan ID.
     */

    public function update(Request $request, $id)
    {
        $data = IndikatorPenilaian::findOrFail($id);
        $data->update($request->all());
        return response()->json(['success' => true, 'data' => $data]);
    }

     /**
     * Hapus indikator penilaian.
     *
     * Menghapus data indikator penilaian berdasarkan ID dari database.
     */

    public function destroy($id)
    {
        IndikatorPenilaian::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }
}