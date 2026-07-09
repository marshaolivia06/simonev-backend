<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Akses publik (saat register, belum login)
        if (!$user) {
            $data = Kelas::with('guru')->orderBy('nama_kelas', 'asc')->get();
            return response()->json(['success' => true, 'data' => $data]);
        }

        // Admin → ambil semua kelas
        if ($user->role === 'admin') {
            $data = Kelas::with('guru')->orderBy('nama_kelas', 'asc')->get();
            return response()->json(['success' => true, 'data' => $data]);
        }

        // Guru → filter by id_guru
        if ($user->role === 'guru') {
            $guru = $user->guru;
            if ($guru) {
                $data = Kelas::with('guru')
                             ->where('id_guru', $guru->id_guru)
                             ->orderBy('nama_kelas', 'asc')
                             ->get();
                return response()->json(['success' => true, 'data' => $data]);
            }
        }

        return response()->json(['success' => true, 'data' => []]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kelas'   => 'required|string',
            'id_guru'      => 'nullable|exists:guru,id_guru',
            'tahun_ajaran' => 'required|string',
        ]);

        $data = Kelas::create($request->only(['nama_kelas', 'id_guru', 'tahun_ajaran']));
        $data->load('guru'); // ← biar response langsung include data guru
        return response()->json(['success' => true, 'data' => $data], 201);
    }

    public function show($id)
    {
        $data = Kelas::with('guru')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_kelas'   => 'required|string',
            'id_guru'      => 'nullable|exists:guru,id_guru',
            'tahun_ajaran' => 'required|string',
        ]);

        $data = Kelas::findOrFail($id);
        $data->update($request->only(['nama_kelas', 'id_guru', 'tahun_ajaran']));
        $data->load('guru');
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function destroy($id)
    {
        Kelas::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }
}