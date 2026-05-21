<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Guru;
use App\Models\OrangTua;
use App\Models\Anak;
use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'username'  => 'required|string|unique:users,username',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6',
            'role'      => 'required|in:guru,orang_tua',
            'nama'      => 'required|string',
            'nik'       => 'required|string|size:16',
            'no_telp'   => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'username' => $request->username,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => $request->role,
                'status'   => 'pending',
            ]);

            if ($request->role === 'guru') {
                $suratTugasPath = null;
                if ($request->hasFile('surat_tugas')) {
                    $suratTugasPath = $request->file('surat_tugas')->store('surat_tugas', 'public');
                }

                Guru::create([
                    'id_user'      => $user->id,
                    'nik'          => $request->nik,
                    'nama_guru'    => $request->nama,
                    'no_telp'      => $request->no_telp,
                    'email'        => $user->email, // FIX: sinkronisasi email dari users
                    'nip'          => $request->nip,
                    'nama_lembaga' => $request->nama_lembaga,
                    'jabatan'      => $request->jabatan,
                    'surat_tugas'  => $suratTugasPath,
                    'jenis_kelamin'=> $request->jenis_kelamin ?? 'L',
                    'alamat'       => $request->alamat,
                ]);
            }

            if ($request->role === 'orang_tua') {

    $fotoKtpPath = null;

    if ($request->hasFile('foto_ktp')) {
        $fotoKtpPath = $request->file('foto_ktp')->store('foto_ktp', 'public');
    }

    // simpan data orang tua
    $orangTua = OrangTua::create([
        'id_user'       => $user->id,
        'nik'           => $request->nik,
        'nama_orangtua' => $request->nama,
        'no_telp'       => $request->no_telp,
        'alamat'        => $request->alamat,
        'pekerjaan'     => $request->pekerjaan,
        'hubungan'      => $request->hubungan,
        'nama_anak'     => $request->nama_anak,
        'kelas_anak'    => $request->kelas_anak,
        'foto_ktp'      => $fotoKtpPath,
    ]);

    // cari id_kelas berdasarkan nama kelas
    $kelas = Kelas::where('nama_kelas', $request->kelas_anak)->first();

    // simpan data anak
    Anak::create([
        'id_kelas'      => $kelas ? $kelas->id_kelas : null,
        'id_orangtua'   => $orangTua->id_orangtua,
        'nama_anak'     => $request->nama_anak,
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => null,
    ]);
}
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran berhasil, menunggu verifikasi admin',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau password salah',
            ], 401);
        }

        if ($user->status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Akun kamu masih menunggu verifikasi admin.',
            ], 403);
        }

        if ($user->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Akun kamu ditolak oleh admin.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'guru') {
            $user->load('guru');
        }

        if ($user->role === 'orang_tua') {
            $user->load('orangTua');
        }

        return response()->json([
            'success' => true,
            'data'    => $user,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'username'      => 'required|string|unique:users,username,' . $user->id,
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'nama'          => 'required|string',
            'nik'           => 'required|string|size:16',
            'no_telp'       => 'nullable|string',
            'alamat'        => 'nullable|string',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'password'      => 'nullable|min:6',
        ]);

        // Update tabel users
        $user->username = $request->username;
        $user->email    = $request->email;
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        // Update tabel guru
        if ($user->role === 'guru') {
            $user->guru()->update([
                'nama_guru'     => $request->nama,
                'nik'           => $request->nik,
                'no_telp'       => $request->no_telp,
                'alamat'        => $request->alamat,
                'jenis_kelamin' => $request->jenis_kelamin,
                'tanggal_lahir' => $request->tanggal_lahir,
                'email'         => $request->email, 
               
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
        ]);
    }
}