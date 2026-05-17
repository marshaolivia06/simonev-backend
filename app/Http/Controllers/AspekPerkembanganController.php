<?php
namespace App\Http\Controllers;

use App\Models\AspekPerkembangan;
use Illuminate\Http\Request;

class AspekPerkembanganController extends Controller
{
    public function index()
    {
        $data = AspekPerkembangan::with('indikator')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_aspek'    => 'required|string',
            'definisi_aspek'=> 'nullable|string',
        ]);

        $data = AspekPerkembangan::create($request->all());
        return response()->json(['success' => true, 'data' => $data], 201);
    }

    public function show($id)
    {
        $data = AspekPerkembangan::with('indikator')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request, $id)
    {
        $data = AspekPerkembangan::findOrFail($id);
        $data->update($request->all());
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function destroy($id)
    {
        AspekPerkembangan::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }
}