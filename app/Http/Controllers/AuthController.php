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
    // ← PERUBAHAN: endpoint baru untuk ambil daftar kelas
    public function getKelas()
    {
        $kelas = Kelas::all(['id_kelas', 'nama_kelas']);

        return response()->json([
            'success' => true,
            'data'    => $kelas,
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'username'           => 'required|string|unique:users,username',
            'email'              => 'required|email|unique:users,email',
            'password'           => 'required|min:6',
            'role'               => 'required|in:guru,orang_tua',
            'nama'               => 'required|string',
            'nik'                => 'required|string|size:16',
            'no_telp'            => 'required|string',
            'alamat'             => 'nullable|string',
            'jabatan'            => 'nullable|string',
            'tanggal_lahir'      => 'nullable|date',
            'jenis_kelamin'      => 'nullable|in:L,P',
            'tanggal_lahir_anak' => 'nullable|date',
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
                    'id_user'       => $user->id,
                    'nik'           => $request->nik,
                    'nama_guru'     => $request->nama,
                    'no_telp'       => $request->no_telp,
                    'email'         => $user->email,
                    'nip'           => $request->nip,
                    'jabatan'       => $request->jabatan,
                    'alamat'        => $request->alamat,
                    'tanggal_lahir' => $request->tanggal_lahir,
                    'surat_tugas'   => $suratTugasPath,
                    'jenis_kelamin' => $request->jenis_kelamin ?? 'L',
                ]);
            }

            if ($request->role === 'orang_tua') {
                $fotoKtpPath = null;
                if ($request->hasFile('foto_ktp')) {
                    $fotoKtpPath = $request->file('foto_ktp')->store('foto_ktp', 'public');
                }

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

                $kelas = Kelas::where('nama_kelas', $request->kelas_anak)->first();

                if (!$kelas) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Kelas "' . $request->kelas_anak . '" tidak ditemukan. Pastikan nama kelas sesuai.',
                    ], 422);
                }

                Anak::create([
                    'id_kelas'      => $kelas->id_kelas,
                    'id_orangtua'   => $orangTua->id_orangtua,
                    'nama_anak'     => $request->nama_anak,
                    'jenis_kelamin' => $request->jenis_kelamin ?? 'L',
                    'tanggal_lahir' => $request->tanggal_lahir_anak,
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

        // ← PERUBAHAN: cukup 1 pengecekan, hanya 'approved' yang bisa login
        if ($user->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => match($user->status) {
                    'pending'  => 'Akun kamu masih menunggu verifikasi admin.',
                    'rejected' => 'Akun kamu ditolak oleh admin.',
                    default    => 'Akun kamu belum diverifikasi admin.',
                },
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
            $user->load('orangTua.anak.kelas');
        }

        return response()->json([
            'success' => true,
            'data'    => $user,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $rules = [
            'username' => 'required|string|unique:users,username,' . $user->id,
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
        ];

        if ($user->role === 'guru') {
            $rules['nama']          = 'required|string';
            $rules['nik']           = 'required|string|size:16';
            $rules['no_telp']       = 'nullable|string';
            $rules['alamat']        = 'nullable|string';
            $rules['jabatan']       = 'nullable|string';
            $rules['jenis_kelamin'] = 'nullable|in:L,P';
            $rules['tanggal_lahir'] = 'nullable|date';
        }

        $request->validate($rules);

        $user->username = $request->username;
        $user->email    = $request->email;
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        if ($user->role === 'guru') {
            $user->guru()->update([
                'nama_guru'     => $request->nama,
                'nik'           => $request->nik,
                'no_telp'       => $request->no_telp,
                'alamat'        => $request->alamat,
                'jabatan'       => $request->jabatan,
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