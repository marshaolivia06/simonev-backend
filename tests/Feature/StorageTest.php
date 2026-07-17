<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageTest extends TestCase
{
    use RefreshDatabase;

    protected string $testFolder = 'test-storage-folder';
    protected string $testFilename = 'test-file.txt';

    protected function testFilePath(): string
    {
        return storage_path("app/public/{$this->testFolder}/{$this->testFilename}");
    }

    protected function tearDown(): void
    {
        // Bersihkan file & folder fisik yang dibuat selama test
        $path = $this->testFilePath();
        if (file_exists($path)) {
            unlink($path);
        }
        $dir = dirname($path);
        if (is_dir($dir)) {
            rmdir($dir);
        }

        parent::tearDown();
    }

    public function test_serve_file_mengembalikan_404_jika_file_tidak_ada()
    {
        $response = $this->get("/api/storage-file/{$this->testFolder}/tidak-ada.txt");

        $response->assertStatus(404);
    }

    public function test_serve_file_berhasil_mengembalikan_file_yang_ada()
    {
        $dir = dirname($this->testFilePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->testFilePath(), 'konten dummy untuk test');

        $response = $this->get("/api/storage-file/{$this->testFolder}/{$this->testFilename}");

        $response->assertStatus(200)
                 ->assertHeader('Access-Control-Allow-Origin') // nilainya diatur oleh config/cors.php, bukan '*' secara harfiah
                 ->assertHeader('Cache-Control'); // urutan directive (public/max-age) dinormalisasi Laravel, jadi cukup pastikan headernya ada

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=86400', $cacheControl);
    }

    public function test_serve_file_bisa_diakses_tanpa_login()
    {
        $dir = dirname($this->testFilePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->testFilePath(), 'konten dummy');

        // Tidak ada Sanctum::actingAs() di sini — pastikan endpoint memang publik
        $response = $this->get("/api/storage-file/{$this->testFolder}/{$this->testFilename}");

        $response->assertStatus(200);
    }
}
