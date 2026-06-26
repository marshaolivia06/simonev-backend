<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StorageController extends Controller
{
    /**
     * Ambil file dari storage.
     *
     * Mengembalikan file dari storage berdasarkan folder dan nama file,
     * dengan header CORS untuk akses publik.
     */
    public function serveFile($folder, $filename)
    {
        $path = storage_path("app/public/{$folder}/{$filename}");
        if (!file_exists($path)) {
            abort(404);
        }
        return response()->file($path, [
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}