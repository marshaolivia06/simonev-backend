<?php
namespace App\Http\Controllers;

use App\Models\Observasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ObservasiController extends Controller
{
    public function index()
    {
        $data = Observasi::with('guru', 'anak', 'indikator')->get();
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
        $data = Observasi::with('guru', 'anak', 'indikator')->findOrFail($id);
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
}