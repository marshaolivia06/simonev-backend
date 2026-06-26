<?php

namespace App\Http\Controllers;

use App\Models\Observasi;
use App\Models\AspekPerkembangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ObservasiController extends Controller
{
    /**
     * Ambil semua data observasi.
     *
     * Mengembalikan daftar seluruh observasi, bisa difilter berdasarkan id_anak dan semester.
     */

    // ── GET /observasi ────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Observasi::with(['anak', 'indikator.aspek', 'guru'])
            ->orderBy('tanggal', 'desc');

        if ($request->id_anak) {
            $query->where('id_anak', $request->id_anak);
        }
        if ($request->semester) {
            $query->where('semester', $request->semester);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    /**
     * Tambah data observasi baru.
     *
     * Menyimpan satu data observasi perkembangan anak oleh guru, termasuk upload foto.
     */

    // ── POST /observasi (single — termasuk upload foto) ───────────
    public function store(Request $request)
    {
        $request->validate([
            'id_anak'     => 'required|exists:anak,id_anak',
            'id_indikator'=> 'required|exists:indikator_penilaian,id_indikator',
            'nilai'       => 'required|in:BB,MB,BSH,BSB',
            'tanggal'     => 'required|date',
            'semester'    => 'required|string',
        ]);

        $guru = Auth::user()->guru;

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('foto-observasi', 'public');
        }

        // keterangan_aspek bisa dikirim sebagai JSON string atau array
        $keteranganAspek = null;
        if ($request->has('keterangan_aspek')) {
            $ka = $request->keterangan_aspek;
            $keteranganAspek = is_string($ka) ? json_decode($ka, true) : $ka;
        }

        $observasi = Observasi::create([
            'id_anak'          => $request->id_anak,
            'id_indikator'     => $request->id_indikator,
            'id_guru'          => $guru?->id_guru,
            'nilai'            => $request->nilai,
            'komentar'         => $request->komentar,
            'keterangan_aspek' => $keteranganAspek,
            'foto'             => $fotoPath,
            'tanggal'          => $request->tanggal,
            'semester'         => $request->semester,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $observasi->load(['indikator.aspek', 'guru']),
        ], 201);
    }

    /**
     * Tambah observasi secara batch.
     *
     * Menyimpan beberapa observasi sekaligus untuk satu anak dalam satu sesi penilaian,
     * beserta komentar global dan keterangan per aspek perkembangan.
     */

    public function storeBatch(Request $request)
    {
        $request->validate([
            'id_anak'            => 'required|exists:anak,id_anak',
            'semester'           => 'required|string',
            'tanggal'            => 'required|date',
            'komentar'           => 'nullable|string',
            'keterangan_aspek'   => 'nullable|array',   // [BARU]
            'penilaian'          => 'required|array|min:1',
            'penilaian.*.id_indikator' => 'required|exists:indikator_penilaian,id_indikator',
            'penilaian.*.nilai'        => 'required|in:BB,MB,BSH,BSB',
        ]);

        $guru = Auth::user()->guru;

        // keterangan_aspek disimpan di SETIAP baris observasi sesi ini
        // sehingga byAnak() bisa mengambilnya dari baris mana saja
        $keteranganAspek = $request->keterangan_aspek ?? [];

        $inserted = [];
        foreach ($request->penilaian as $item) {
            $observasi = Observasi::create([
                'id_anak'          => $request->id_anak,
                'id_indikator'     => $item['id_indikator'],
                'id_guru'          => $guru?->id_guru,
                'nilai'            => $item['nilai'],
                'komentar'         => $request->komentar,       // komentar global
                'keterangan_aspek' => $keteranganAspek,         // [BARU] per aspek
                'foto'             => $item['foto'] ?? null,
                'tanggal'          => $request->tanggal,
                'semester'         => $request->semester,
            ]);
            $inserted[] = $observasi;
        }

        return response()->json([
            'success' => true,
            'message' => count($inserted) . ' observasi berhasil disimpan.',
            'data'    => $inserted,
        ], 201);
    }

    /**
     * Ambil rekap observasi per anak.
     *
     * Mengembalikan laporan perkembangan anak berdasarkan ID anak,
     * berisi rekap per aspek, riwayat observasi, komentar, dan keterangan aspek.
     * Bisa difilter berdasarkan semester.
     */

    public function byAnak(Request $request, $id_anak)
    {
        $semester = $request->query('semester');

        $query = Observasi::with(['indikator.aspek', 'guru', 'anak.kelas'])
            ->where('id_anak', $id_anak)
            ->orderBy('tanggal', 'desc');

        if ($semester) {
            $query->where('semester', $semester);
        }

        $observasiList = $query->get();

        if ($observasiList->isEmpty()) {
            $anak = \App\Models\Anak::with('kelas')->find($id_anak);
            return response()->json([
                'success' => true,
                'data'    => [
                    'anak'             => $anak,
                    'rekap_aspek'      => [],
                    'riwayat'          => [],
                    'komentar'         => '',
                    'keterangan_aspek' => (object) [],
                    'total'            => 0,
                ],
            ]);
        }

        // ── Rekap per aspek ───────────────────────────────────────
        $rekapMap = [];
        foreach ($observasiList as $obs) {
            $aspekNama = $obs->indikator?->aspek?->nama_aspek ?? 'Lainnya';
            if (!isset($rekapMap[$aspekNama])) {
                $rekapMap[$aspekNama] = ['aspek' => $aspekNama, 'jumlah' => 0, 'nilai' => null];
            }
            $rekapMap[$aspekNama]['jumlah']++;
        }
        $rekapAspek = array_values($rekapMap);

        // ── Komentar global: ambil dari observasi terbaru yang ada komentar ──
        $komentarGlobal = '';
        foreach ($observasiList as $obs) {
            if ($obs->komentar) {
                $komentarGlobal = $obs->komentar;
                break;
            }
        }

        $keteranganAspekMerged = [];
        // Proses dari terlama ke terbaru supaya terbaru menimpa
        foreach ($observasiList->reverse() as $obs) {
            $ka = $obs->keterangan_aspek; // sudah di-cast ke array oleh model
            if (is_array($ka)) {
                foreach ($ka as $aspekId => $teks) {
                    if (!empty(trim((string) $teks))) {
                        $keteranganAspekMerged[(string) $aspekId] = $teks;
                    }
                }
            }
        }

        // ── Juga sertakan keterangan berdasarkan nama aspek (untuk frontend yang pakai nama) ──
        // Cari nama aspek dari id_aspek
        if (!empty($keteranganAspekMerged)) {
            $aspekNamaMap = AspekPerkembangan::whereIn('id_aspek', array_keys($keteranganAspekMerged))
                ->pluck('nama_aspek', 'id_aspek')
                ->toArray();

            foreach ($keteranganAspekMerged as $aspekId => $teks) {
                if (isset($aspekNamaMap[$aspekId])) {
                    // Simpan juga dengan key nama aspek supaya frontend bisa match keduanya
                    $keteranganAspekMerged[$aspekNamaMap[$aspekId]] = $teks;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'anak'             => $observasiList->first()->anak,
                'rekap_aspek'      => $rekapAspek,
                'riwayat'          => $observasiList->values(),
                'komentar'         => $komentarGlobal,
                'keterangan_aspek' => (object) $keteranganAspekMerged, // [BARU]
                'total'            => $observasiList->count(),
            ],
        ]);
    }

    /**
     * Ambil detail observasi.
     *
     * Mengembalikan data detail observasi berdasarkan ID.
     */

    public function show($id)
    {
        $observasi = Observasi::with(['anak', 'indikator.aspek', 'guru'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $observasi]);
    }

    /**
     * Update data observasi.
     *
     * Mengubah data observasi berdasarkan ID, termasuk update foto dan keterangan aspek.
     */

    // ── PUT /observasi/{id} ───────────────────────────────────────
    public function update(Request $request, $id)
    {
        $observasi = Observasi::findOrFail($id);

        $request->validate([
            'nilai'   => 'sometimes|in:BB,MB,BSH,BSB',
            'tanggal' => 'sometimes|date',
        ]);

        if ($request->hasFile('foto')) {
            if ($observasi->foto) Storage::disk('public')->delete($observasi->foto);
            $observasi->foto = $request->file('foto')->store('foto-observasi', 'public');
        }

        if ($request->has('keterangan_aspek')) {
            $ka = $request->keterangan_aspek;
            $observasi->keterangan_aspek = is_string($ka) ? json_decode($ka, true) : $ka;
        }

        $observasi->fill($request->only(['nilai', 'komentar', 'tanggal', 'semester']));
        $observasi->save();

        return response()->json(['success' => true, 'data' => $observasi]);
    }

    /**
     * Hapus data observasi.
     *
     * Menghapus data observasi berdasarkan ID beserta foto yang tersimpan.
     */

    // ── DELETE /observasi/{id} ────────────────────────────────────
    public function destroy($id)
    {
        $observasi = Observasi::findOrFail($id);
        if ($observasi->foto) Storage::disk('public')->delete($observasi->foto);
        $observasi->delete();
        return response()->json(['success' => true, 'message' => 'Observasi dihapus.']);
    }
}
