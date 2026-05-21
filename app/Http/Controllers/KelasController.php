<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    public function index()
    {
        $data = Kelas::all();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kelas'   => 'required|string',
            'wali_kelas'   => 'required|string',
            'tahun_ajaran' => 'required|string',
        ]);

        $data = Kelas::create($request->all());
        return response()->json(['success' => true, 'data' => $data], 201);
    }

    public function show($id)
    {
        $data = Kelas::findOrFail($id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request, $id)
    {
        $data = Kelas::findOrFail($id);
        $data->update($request->only(['nama_kelas', 'wali_kelas', 'tahun_ajaran']));
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function destroy($id)
    {
        Kelas::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }
}