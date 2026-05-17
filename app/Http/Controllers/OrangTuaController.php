<?php
namespace App\Http\Controllers;

use App\Models\OrangTua;
use Illuminate\Http\Request;

class OrangTuaController extends Controller
{
    public function index()
    {
        $data = OrangTua::with('user', 'anak')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

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

    public function show($id)
    {
        $data = OrangTua::with('user', 'anak')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request, $id)
    {
        $data = OrangTua::findOrFail($id);
        $data->update($request->all());
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function destroy($id)
    {
        OrangTua::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }
}