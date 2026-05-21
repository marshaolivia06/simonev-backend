<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class VerifikasiController extends Controller
{
    // GET /api/verifikasi
    public function index()
    {
        $data = User::with(['guru', 'orangTua'])
                    ->whereIn('status', ['pending', 'approved', 'rejected'])
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

    // POST /api/verifikasi/{id}/accept
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

        return response()->json([
            'success' => true,
            'message' => "Akun {$user->username} berhasil diverifikasi.",
        ]);
    }

    // POST /api/verifikasi/{id}/reject
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
}