<?php
namespace App\Http\Controllers;

use App\Models\Guru;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuruController extends Controller
{
    // GET /api/guru
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

    // GET /api/guru/{id}
    public function show($id)
    {
        $data = Guru::with('user', 'kelas', 'observasi')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    // POST /api/guru
    public function store(Request $request)
    {
        $request->validate([
            'id_user'       => 'nullable|exists:users,id',
            'nik'           => 'required|string|max:20|unique:guru,nik',
            'nama_guru'     => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'no_telp'       => 'nullable|string|max:20',
            'email'         => 'nullable|email|unique:guru,email',
            'alamat'        => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'nama_lembaga'  => 'nullable|string|max:255',
            'jabatan'       => 'nullable|string|max:255',
        ]);

        // PERBAIKAN: ganti $request->only() → $request->input() per field
        // supaya id_user tetap masuk sebagai NULL jika tidak dikirim
        $data = Guru::create([
            'id_user'       => $request->input('id_user'),       // NULL jika tidak dikirim ✓
            'nik'           => $request->input('nik'),
            'nama_guru'     => $request->input('nama_guru'),
            'jenis_kelamin' => $request->input('jenis_kelamin'),
            'no_telp'       => $request->input('no_telp'),
            'email'         => $request->input('email'),
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

    // PUT /api/guru/{guru}
    public function update(Request $request, Guru $guru)
    {
        $request->validate([
            'id_user'       => 'nullable|exists:users,id',
            'nik'           => ['sometimes', 'string', 'max:20',
                                Rule::unique('guru', 'nik')->ignore($guru->id_guru, 'id_guru')],
            'nama_guru'     => 'sometimes|string|max:255',
            'jenis_kelamin' => 'sometimes|in:L,P',
            'no_telp'       => 'nullable|string|max:20',
            'email'         => ['nullable', 'email',
                                Rule::unique('guru', 'email')->ignore($guru->id_guru, 'id_guru')],
            'alamat'        => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'nama_lembaga'  => 'nullable|string|max:255',
            'jabatan'       => 'nullable|string|max:255',
        ]);

        // update() pakai only() tetap aman karena hanya update field yang dikirim saja
        $guru->update($request->only([
            'id_user', 'nik', 'nama_guru', 'jenis_kelamin',
            'no_telp', 'email', 'alamat', 'tanggal_lahir',
            'nama_lembaga', 'jabatan',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Data guru berhasil diperbarui.',
            'data'    => $guru->fresh(),
        ]);
    }

    // DELETE /api/guru/{guru}
    public function destroy(Guru $guru)
    {
        $guru->delete();
        return response()->json([
            'success' => true,
            'message' => 'Data guru berhasil dihapus.',
        ]);
    }
}