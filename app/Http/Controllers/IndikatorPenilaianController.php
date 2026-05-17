<?php
namespace App\Http\Controllers;

use App\Models\IndikatorPenilaian;
use Illuminate\Http\Request;

class IndikatorPenilaianController extends Controller
{
    public function index()
    {
        $data = IndikatorPenilaian::with('aspek')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_aspek'      => 'required|exists:aspek_perkembangan,id_aspek',
            'nama_indikator'=> 'required|string',
            'nama_kegiatan' => 'nullable|string',
        ]);

        $data = IndikatorPenilaian::create($request->all());
        return response()->json(['success' => true, 'data' => $data], 201);
    }

    public function show($id)
    {
        $data = IndikatorPenilaian::with('aspek')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request, $id)
    {
        $data = IndikatorPenilaian::findOrFail($id);
        $data->update($request->all());
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function destroy($id)
    {
        IndikatorPenilaian::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }
}