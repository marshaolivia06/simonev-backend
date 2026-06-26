<?php
namespace App\Http\Controllers;

use App\Models\Pengumuman;
use Illuminate\Http\Request;

class PengumumanController extends Controller
{
    /**
     * Ambil semua pengumuman.
     *
     * Mengembalikan daftar seluruh pengumuman beserta data user pembuatnya,
     * diurutkan dari yang terbaru.
     */

    public function index()
    {
        $data = Pengumuman::with('user')->latest()->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Tambah pengumuman baru.
     *
     * Menyimpan pengumuman baru ke database dengan kategori Kegiatan, Libur, Penting, atau Info.
     * ID user diambil otomatis dari token pengguna yang sedang login.
     */

    public function store(Request $request)
    {
        $request->validate([
            'judul_pengumuman' => 'required|string',
            'isi_pengumuman'   => 'required|string',
            'tanggal'          => 'required|string',
            'kategori'         => 'required|in:Kegiatan,Libur,Penting,Info',
        ]);

        $data = Pengumuman::create([
            'id_user'          => auth()->id(), // ← ambil dari token, bukan request
            'judul_pengumuman' => $request->judul_pengumuman,
            'isi_pengumuman'   => $request->isi_pengumuman,
            'tanggal'          => $request->tanggal,
            'kategori'         => $request->kategori,
        ]);

        return response()->json(['success' => true, 'data' => $data], 201);
    }

    /**
     * Ambil detail pengumuman.
     *
     * Mengembalikan data detail pengumuman berdasarkan ID beserta data user pembuatnya.
     */

    public function show($id)
    {
        $data = Pengumuman::with('user')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Update pengumuman.
     *
     * Mengubah data pengumuman berdasarkan ID, semua field bersifat opsional.
     */

    public function update(Request $request, $id)
    {
        $request->validate([
            'judul_pengumuman' => 'sometimes|string',
            'isi_pengumuman'   => 'sometimes|string',
            'tanggal'          => 'sometimes|string',
            'kategori'         => 'sometimes|in:Kegiatan,Libur,Penting,Info',
        ]);

        $data = Pengumuman::findOrFail($id);
        $data->update([
            'judul_pengumuman' => $request->judul_pengumuman ?? $data->judul_pengumuman,
            'isi_pengumuman'   => $request->isi_pengumuman   ?? $data->isi_pengumuman,
            'tanggal'          => $request->tanggal          ?? $data->tanggal,
            'kategori'         => $request->kategori         ?? $data->kategori,
        ]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Hapus pengumuman.
     *
     * Menghapus data pengumuman berdasarkan ID dari database.
     */

    public function destroy($id)
    {
        Pengumuman::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }
}