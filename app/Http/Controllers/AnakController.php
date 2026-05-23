<?php

namespace App\Http\Controllers;

use App\Models\Anak;
use Illuminate\Http\Request;

class AnakController extends Controller
{
    public function index()
    {
        $data = Anak::with('kelas', 'orangTua.user')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

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

    public function show($id)
    {
        $data = Anak::with('kelas', 'orangTua.user', 'observasi')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request, $id)
    {
        $data = Anak::findOrFail($id);
        $data->update($request->all());
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function destroy($id)
    {
        Anak::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }
}