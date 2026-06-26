<?php
namespace App\Http\Controllers;

use App\Models\OrangTua;
use Illuminate\Http\Request;

class OrangTuaController extends Controller
{
    /**
     * Ambil semua data orang tua.
     *
     * Mengembalikan daftar seluruh orang tua beserta relasi user dan data anak.
     */

    public function index()
    {
        $data = OrangTua::with('user', 'anak')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Tambah data orang tua baru.
     *
     * Menyimpan data orang tua baru ke database beserta relasi user.
     */

    public function store(Request $request)
    {
        $request->validate([
            'id_user'       => 'required|exists:users,id',
            'nik'           => 'required|unique:orang_tua,nik',
            'nama_orangtua' => 'required|string',
            'no_telp'       => 'nullable|string',
            'alamat'        => 'nullable|string',
            'pekerjaan'     => 'nullable|string',
        ]);

        $data = OrangTua::create($request->all());
        return response()->json(['success' => true, 'data' => $data], 201);
    }

    /**
     * Ambil detail data orang tua.
     *
     * Mengembalikan data detail orang tua berdasarkan ID beserta relasi user dan anak.
     */

    public function show($id)
    {
        $data = OrangTua::with('user', 'anak')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Update data orang tua.
     *
     * Mengubah data orang tua berdasarkan ID.
     */

    public function update(Request $request, $id)
    {
        $data = OrangTua::findOrFail($id);
        $data->update($request->all());
        return response()->json(['success' => true, 'data' => $data]);
    }

     /**
     * Hapus data orang tua.
     *
     * Menghapus data orang tua berdasarkan ID dari database.
     */

    public function destroy($id)
    {
        OrangTua::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }   

    /**
     * Ambil profil anak milik orang tua.
     *
     * Mengembalikan data anak beserta kelas dari orang tua yang sedang login.
     */
    
    public function profilAnak(Request $request)
    {
        $user = $request->user();

        $orangTua = OrangTua::with('anak.kelas')
            ->where('id_user', $user->id)
            ->first();

        return response()->json([
    'success' => true,
    'data'    => $orangTua ? $orangTua->anak : null
]);
    }
}