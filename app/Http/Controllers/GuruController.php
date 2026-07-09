<?php
namespace App\Http\Controllers;

use App\Models\Guru;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuruController extends Controller
{
     /**
     * Ambil semua data guru.
     *
     * Mengembalikan daftar seluruh guru yang sudah diverifikasi admin, diurutkan berdasarkan nama.
     */

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
    
    /**
     * Ambil detail data guru.
     *
     * Mengembalikan data detail guru berdasarkan ID beserta relasi user dan observasi.
     */

    public function show($id)
    {
        $data = Guru::with('user', 'observasi')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Tambah data guru baru.
     *
     * Menyimpan data guru baru ke database.
     */

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

    /**
     * Update data guru.
     *
     * Mengubah data guru berdasarkan ID.
     */

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

    /**
     * Hapus data guru.
     *
     * Menghapus data guru berdasarkan ID dari database.
     */

    public function destroy(Guru $guru)
    {
        $guru->delete();
        return response()->json([
            'success' => true,
            'message' => 'Data guru berhasil dihapus.',
        ]);
    }

    /**
     * Ambil data dashboard guru.
     *
     * Mengembalikan ringkasan data kelas guru yang sedang login,
     * termasuk total anak dan jumlah per skala perkembangan BB/MB/BSH/BSB.
     */

    public function dashboard(Request $request)
    {
        $guru = $request->user();

        $idGuru = $guru->guru->id_guru ?? null;
        $kelas = \App\Models\Kelas::where('id_guru', $idGuru)->first();

        if (!$kelas) {
            return response()->json([
                'success' => true,
                'data' => [
                    'nama_kelas' => null,
                    'total_anak' => 0,
                    'BB' => 0, 'MB' => 0, 'BSH' => 0, 'BSB' => 0,
                    'belum_dinilai' => 0,
                ]
            ]);
        }

        $anakList  = \App\Models\Anak::where('id_kelas', $kelas->id_kelas)->pluck('id_anak');
        $totalAnak = $anakList->count();

        // Semua observasi anak di kelas ini
        $semuaObservasi = \App\Models\Observasi::whereIn('id_anak', $anakList)
            ->select('id_anak', 'nilai')
            ->get();

        $nilaiOrder = ['BB' => 1, 'MB' => 2, 'BSH' => 3, 'BSB' => 4];
        $labelOrder = ['BB', 'MB', 'BSH', 'BSB']; // index 0-3

        // Hitung nilai dominan per anak (rata-rata semua observasi)
        $nilaiPerAnak = $semuaObservasi
            ->groupBy('id_anak')
            ->map(function ($obs) use ($nilaiOrder, $labelOrder) {
                $rata = $obs->pluck('nilai')
                    ->filter()
                    ->map(fn($n) => $nilaiOrder[$n] ?? 0)
                    ->avg();
                return $rata ? $labelOrder[(int) round($rata) - 1] : null;
            })
            ->filter(); // buang anak yang belum ada nilai

        $counts = $nilaiPerAnak->countBy()->all();

        return response()->json([
            'success' => true,
            'data' => [
                'nama_kelas'    => $kelas->nama_kelas,
                'total_anak'    => $totalAnak,
                'BB'            => $counts['BB']  ?? 0,
                'MB'            => $counts['MB']  ?? 0,
                'BSH'           => $counts['BSH'] ?? 0,
                'BSB'           => $counts['BSB'] ?? 0,
                'belum_dinilai' => $totalAnak - $nilaiPerAnak->count(),
            ]
        ]);
    }

    /**
     * Ambil daftar anak berdasarkan skala perkembangan.
     *
     * Mengembalikan daftar anak di kelas guru yang sedang login
     * berdasarkan skala perkembangan tertentu (BB, MB, BSH, atau BSB).
     */
    
    public function anakBySkala(Request $request)
    {
        $request->validate([
            'skala' => 'required|in:BB,MB,BSH,BSB',
        ]);

        $skala = $request->input('skala');
        $guru = $request->user();

        $idGuru = $guru->guru->id_guru ?? null;
        $kelas = \App\Models\Kelas::where('id_guru', $idGuru)->first();

        if (!$kelas) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $anakList = \App\Models\Anak::where('id_kelas', $kelas->id_kelas)
            ->select('id_anak', 'nama_anak')
            ->get();

        $semuaObservasi = \App\Models\Observasi::whereIn('id_anak', $anakList->pluck('id_anak'))
            ->select('id_anak', 'nilai')
            ->get();

        $nilaiOrder = ['BB' => 1, 'MB' => 2, 'BSH' => 3, 'BSB' => 4];
        $labelOrder = ['BB', 'MB', 'BSH', 'BSB'];

        // Hitung nilai dominan (rata-rata) per anak, sama seperti di dashboard()
        $nilaiPerAnak = $semuaObservasi
            ->groupBy('id_anak')
            ->map(function ($obs) use ($nilaiOrder, $labelOrder) {
                $rata = $obs->pluck('nilai')
                    ->filter()
                    ->map(fn($n) => $nilaiOrder[$n] ?? 0)
                    ->avg();
                return $rata ? $labelOrder[(int) round($rata) - 1] : null;
            })
            ->filter();

        // Ambil id_anak yang nilai rata-ratanya == skala yang diminta
        $idAnakTerpilih = $nilaiPerAnak->filter(fn($v) => $v === $skala)->keys();

        $hasil = $anakList->whereIn('id_anak', $idAnakTerpilih)
            ->map(fn($a) => [
                'id_anak'   => $a->id_anak,
                'nama_anak' => $a->nama_anak,
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $hasil]);
    }

    /**
     * Ambil guru berdasarkan kelas.
     *
     * Mengembalikan data guru yang menjadi wali kelas berdasarkan ID kelas.
     */
    public function byKelas($id_kelas)
    {
        $kelas = \App\Models\Kelas::find($id_kelas);
        if (!$kelas || !$kelas->id_guru) {
            return response()->json(['success' => false, 'data' => null]);
        }
        $guru = \App\Models\Guru::find($kelas->id_guru);
        return response()->json(['success' => true, 'data' => $guru]);
    }
}
