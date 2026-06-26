<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Anak;
use App\Models\Kelas;
use Illuminate\Http\Request;

class VerifikasiController extends Controller
{
    /**
     * Ambil semua data pengguna untuk verifikasi.
     *
     * Mengembalikan daftar seluruh pengguna non-admin beserta detail guru atau orang tua,
     * diurutkan dari yang terbaru.
     */

    public function index()
    {
        $data = User::with(['guru', 'orangTua'])
                    ->whereIn('status', ['pending', 'approved', 'rejected'])
                    ->where('role', '!=', 'admin')
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($user) {
                        return [
                            'id'         => $user->id,
                            'username'   => $user->username,
                            'email'      => $user->email,
                            'role'       => $user->role,
                            'status'     => $user->status,
                            'created_at' => $user->created_at,
                            'detail'     => $user->role === 'guru'
                                                ? $user->guru
                                                : $user->orangTua,
                        ];
                    });

        return response()->json(['success' => true, 'data' => $data]);
    }

     /**
     * Terima pendaftaran akun.
     *
     * Mengubah status akun pengguna menjadi approved. Jika role orang tua,
     * data anak akan otomatis dibuat dan dimasukkan ke kelas yang sesuai.
     */

    public function accept($id)
    {
        $user = User::findOrFail($id);

        if ($user->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Akun ini sudah diproses sebelumnya.',
            ], 400);
        }

        $user->update(['status' => 'approved']);

        if ($user->role === 'orang_tua') {
            $orangTua = $user->orangTua;
            $kelas    = Kelas::where('nama_kelas', $orangTua->kelas_anak)->first();

            if ($kelas && $orangTua) {
                Anak::create([
                    'id_kelas'      => $kelas->id_kelas,
                    'id_orangtua'   => $orangTua->id_orangtua,
                    'nama_anak'     => $orangTua->nama_anak,
                    'tanggal_lahir' => $orangTua->tanggal_lahir_anak,
                    'jenis_kelamin' => $orangTua->jenis_kelamin_anak,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Akun {$user->username} berhasil diverifikasi.",
        ]);
    }

    /**
     * Tolak pendaftaran akun.
     *
     * Mengubah status akun pengguna menjadi rejected beserta alasan penolakan.
     */

    public function reject(Request $request, $id)
    {
        $request->validate([
            'alasan' => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($id);

        if ($user->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Akun ini sudah diproses sebelumnya.',
            ], 400);
        }

        $user->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => "Akun {$user->username} berhasil ditolak.",
        ]);
    }

    /**
     * Hapus akun pengguna.
     *
     * Menghapus data akun pengguna berdasarkan ID dari database.
     */

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil dihapus.',
        ]);
    }
}