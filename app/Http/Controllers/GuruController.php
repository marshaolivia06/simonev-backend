<?php
namespace App\Http\Controllers;

use App\Models\Guru;
use Illuminate\Http\Request;

class GuruController extends Controller
{
    public function index()
    {
        $data = Guru::with('user', 'kelas')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_user'       => 'required|exists:users,id',
            'nik'           => 'required|unique:guru,nik',
            'nama_guru'     => 'required|string',
            'jenis_kelamin' => 'required|in:L,P',
            'no_telp'       => 'nullable|string',
            'alamat'        => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
        ]);

        $data = Guru::create($request->all());
        return response()->json(['success' => true, 'data' => $data], 201);
    }

    public function show($id)
    {
        $data = Guru::with('user', 'kelas', 'observasi')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request, $id)
    {
        $data = Guru::findOrFail($id);
        $data->update($request->all());
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function destroy($id)
    {
        Guru::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }
}