<?php
namespace App\Http\Controllers;

use App\Models\AspekPerkembangan;
use Illuminate\Http\Request;

class AspekPerkembanganController extends Controller
{
    /**
     * Ambil semua aspek perkembangan.
     *
     * Mengembalikan daftar seluruh aspek perkembangan beserta indikator-indikatornya.
     */

    public function index()
    {
        $data = AspekPerkembangan::with('indikator')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Tambah aspek perkembangan baru.
     *
     * Menyimpan data aspek perkembangan baru ke database.
     */

    public function store(Request $request)
    {
        $request->validate([
            'nama_aspek'    => 'required|string',
            'definisi_aspek'=> 'nullable|string',
        ]);

        $data = AspekPerkembangan::create($request->all());
        return response()->json(['success' => true, 'data' => $data], 201);
    }

     /**
     * Ambil detail aspek perkembangan.
     *
     * Mengembalikan data detail aspek perkembangan berdasarkan ID beserta indikatornya.
     */

    public function show($id)
    {
        $data = AspekPerkembangan::with('indikator')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Update aspek perkembangan.
     *
     * Mengubah data aspek perkembangan berdasarkan ID.
     */

    public function update(Request $request, $id)
    {
        $data = AspekPerkembangan::findOrFail($id);
        $data->update($request->all());
        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Hapus aspek perkembangan.
     *
     * Menghapus data aspek perkembangan berdasarkan ID dari database.
     */

    public function destroy($id)
    {
        AspekPerkembangan::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }
}