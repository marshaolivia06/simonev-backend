<?php
namespace App\Http\Controllers;

use App\Models\Guru;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuruController extends Controller
{
    public function index()
    {
        $data = Guru::with('user')
                    ->where(function ($q) {
                        $q->whereHas('user', fn($u) => $u->where('status', 'approved'))
                          ->orWhereNull('id_user');
                    })
                    ->orderBy('nama_guru')
                    ->get();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function show($id)
    {
        $data = Guru::with('user', 'kelas', 'observasi')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_user'       => 'nullable|exists:users,id',
            'nik'           => 'required|string|max:20|unique:guru,nik',
            'nama_guru'     => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'no_telp'       => 'nullable|string|max:20',
            'alamat'        => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'nama_lembaga'  => 'nullable|string|max:255',
            'jabatan'       => 'nullable|string|max:255',
        ]);

        $data = Guru::create([
            'id_user'       => $request->input('id_user'),
            'nik'           => $request->input('nik'),
            'nama_guru'     => $request->input('nama_guru'),
            'jenis_kelamin' => $request->input('jenis_kelamin'),
            'no_telp'       => $request->input('no_telp'),
            'alamat'        => $request->input('alamat'),
            'tanggal_lahir' => $request->input('tanggal_lahir'),
            'nama_lembaga'  => $request->input('nama_lembaga'),
            'jabatan'       => $request->input('jabatan'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data guru berhasil ditambahkan.',
            'data'    => $data,
        ], 201);
    }

    public function update(Request $request, Guru $guru)
    {
        $request->validate([
            'id_user'       => 'nullable|exists:users,id',
            'nik'           => ['sometimes', 'string', 'max:20',
                                Rule::unique('guru', 'nik')->ignore($guru->id_guru, 'id_guru')],
            'nama_guru'     => 'sometimes|string|max:255',
            'jenis_kelamin' => 'sometimes|in:L,P',
            'no_telp'       => 'nullable|string|max:20',
            'alamat'        => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'nama_lembaga'  => 'nullable|string|max:255',
            'jabatan'       => 'nullable|string|max:255',
        ]);

        $guru->update($request->only([
            'id_user', 'nik', 'nama_guru', 'jenis_kelamin',
            'no_telp', 'alamat', 'tanggal_lahir',
            'nama_lembaga', 'jabatan',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Data guru berhasil diperbarui.',
            'data'    => $guru->fresh(),
        ]);
    }

    public function destroy(Guru $guru)
    {
        $guru->delete();
        return response()->json([
            'success' => true,
            'message' => 'Data guru berhasil dihapus.',
        ]);
    }

    public function dashboard(Request $request)
{
    $guru = $request->user();

    // Ambil kelas yang diajar guru ini
    $kelas = \App\Models\Kelas::where('wali_kelas', $guru->id)->first();

    if (!$kelas) {
        return response()->json([
            'success' => true,
            'data' => [
                'total_anak' => 0,
                'BB' => 0, 'MB' => 0, 'BSH' => 0, 'BSB' => 0,
            ]
        ]);
    }

    // Ambil semua anak di kelas ini
    $idAnak = \App\Models\Anak::where('id_kelas', $kelas->id_kelas)->pluck('id_anak');
    $totalAnak = $idAnak->count();

    // Hitung nilai dari observasi
    $nilai = \App\Models\Observasi::whereIn('id_anak', $idAnak)
        ->selectRaw('nilai, COUNT(*) as jumlah')
        ->groupBy('nilai')
        ->pluck('jumlah', 'nilai');

    return response()->json([
        'success' => true,
        'data' => [
            'nama_kelas'  => $kelas->nama_kelas,
            'total_anak'  => $totalAnak,
            'BB'  => $nilai['BB']  ?? 0,
            'MB'  => $nilai['MB']  ?? 0,
            'BSH' => $nilai['BSH'] ?? 0,
            'BSB' => $nilai['BSB'] ?? 0,
        ]
    ]);
}
}