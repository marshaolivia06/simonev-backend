<?php
namespace App\Http\Controllers;

use App\Models\Observasi;
use App\Models\Anak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ObservasiController extends Controller
{
    public function index()
    {
        $data = Observasi::with('guru', 'anak', 'indikator.aspek')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_guru'      => 'required|exists:guru,id_guru',
            'id_anak'      => 'required|exists:anak,id_anak',
            'id_indikator' => 'required|exists:indikator_penilaian,id_indikator',
            'semester'     => 'required|string',
            'tanggal'      => 'required|date',
            'nilai'        => 'nullable|string',
            'komentar'     => 'nullable|string',
            'foto'         => 'nullable|image|max:2048',
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('observasi', 'public');
        }

        $data = Observasi::create(array_merge($request->except('foto'), ['foto' => $fotoPath]));
        return response()->json(['success' => true, 'data' => $data], 201);
    }

    public function show($id)
    {
        $data = Observasi::with('guru', 'anak', 'indikator.aspek')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request, $id)
    {
        $data = Observasi::findOrFail($id);

        if ($request->hasFile('foto')) {
            if ($data->foto) Storage::disk('public')->delete($data->foto);
            $fotoPath = $request->file('foto')->store('observasi', 'public');
            $data->update(array_merge($request->except('foto'), ['foto' => $fotoPath]));
        } else {
            $data->update($request->except('foto'));
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function destroy($id)
    {
        $data = Observasi::findOrFail($id);
        if ($data->foto) Storage::disk('public')->delete($data->foto);
        $data->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function storeBatch(Request $request)
{
    $request->validate([
        'id_anak'                  => 'required|exists:anak,id_anak',
        'semester'                 => 'required|string',
        'tanggal'                  => 'required|date',
        'komentar'                 => 'nullable|string',
        'penilaian'                => 'required|array|min:1',
        'penilaian.*.id_indikator' => 'required|exists:indikator_penilaian,id_indikator',
        'penilaian.*.nilai'        => 'required|in:BB,MB,BSH,BSB',
    ]);

    $idGuru = auth()->user()->guru->id_guru;

    $saved = [];
    foreach ($request->penilaian as $item) {
        $saved[] = Observasi::create([
            'id_guru'      => $idGuru,
            'id_anak'      => $request->id_anak,
            'id_indikator' => $item['id_indikator'],
            'nilai'        => $item['nilai'],
            'foto'         => $item['foto'] ?? null,
            'semester'     => $request->semester,
            'tanggal'      => $request->tanggal,
            'komentar'     => $request->komentar ?? null,
        ]);
    }

    return response()->json([
        'success' => true,
        'data'    => $saved,
    ], 201);
}

    // ← endpoint khusus untuk halaman laporan
    // GET /observasi/anak/{id_anak}?semester=Semester 1
    public function byAnak($id_anak, Request $request)
    {
        $anak = Anak::with('kelas')->findOrFail($id_anak);

        $query = Observasi::with('indikator.aspek', 'guru')
            ->where('id_anak', $id_anak);

        if ($request->semester) {
            $query->where('semester', $request->semester);
        }

        $observasi = $query->orderBy('tanggal', 'asc')->get();

        // Rekap nilai per aspek (ambil nilai terakhir per aspek)
        $rekapAspek = $observasi
            ->groupBy(fn($o) => $o->indikator->aspek->nama_aspek ?? 'Lainnya')
            ->map(function ($items, $aspek) {
                $nilaiOrder = ['BSB' => 4, 'BSH' => 3, 'MB' => 2, 'BB' => 1];
                $nilaiList  = $items->pluck('nilai')->filter();
                $rata       = $nilaiList->isNotEmpty()
                    ? round($nilaiList->map(fn($n) => $nilaiOrder[$n] ?? 0)->avg())
                    : null;
                $nilaiLabel = array_flip(['BB', 'MB', 'BSH', 'BSB']);
                return [
                    'aspek' => $aspek,
                    'nilai' => $rata ? array_search($rata, ['BB', 'MB', 'BSH', 'BSB']) !== false
                        ? ['BB', 'MB', 'BSH', 'BSB'][$rata - 1]
                        : null : null,
                    'jumlah' => $items->count(),
                ];
            })->values();

        // Komentar guru: ambil komentar terakhir yang tidak kosong
        $komentar = $observasi->whereNotNull('komentar')->last()?->komentar ?? '';

        return response()->json([
            'success' => true,
            'data'    => [
                'anak'       => $anak,
                'rekap_aspek'=> $rekapAspek,
                'riwayat'    => $observasi,
                'komentar'   => $komentar,
                'total'      => $observasi->count(),
            ],
        ]);
    }
}