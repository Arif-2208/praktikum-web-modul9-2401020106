<?php

namespace Tests\Feature;

use App\Models\Buku;
use App\Models\Kategori;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BukuCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_index_menampilkan_dua_buku_seed_dan_kategori(): void
    {
        $response = $this->get('/buku');

        $response->assertStatus(200)
            ->assertSee('Daftar Buku')
            ->assertSee('BK001')
            ->assertSee('Dasar PHP')
            ->assertSee('BK002')
            ->assertSee('Belajar Laravel')
            ->assertSee('Pemrograman');
    }

    public function test_create_menampilkan_form_dan_pilihan_kategori(): void
    {
        $response = $this->get('/buku/create');

        $response->assertStatus(200)
            ->assertSee('Tambah Buku')
            ->assertSee('Basis Data')
            ->assertSee('Pemrograman');
    }

    public function test_store_tambah_bk003_valid(): void
    {
        $basisData = Kategori::where('nama', 'Basis Data')->firstOrFail();

        $response = $this->post('/buku', [
            'kode' => 'BK003',
            'judul' => 'Pengantar SQL',
            'kategori_id' => $basisData->id,
        ]);

        $response->assertRedirect(route('buku.index'))
            ->assertSessionHas('status', 'Buku ditambahkan.');

        $this->assertDatabaseHas('bukus', [
            'kode' => 'BK003',
            'judul' => 'Pengantar SQL',
            'kategori_id' => $basisData->id,
        ]);
    }

    public function test_validasi_menolak_kode_duplikat_dan_judul_kosong(): void
    {
        $basisData = Kategori::where('nama', 'Basis Data')->firstOrFail();

        // Kode duplikat BK001
        $responseDup = $this->from('/buku/create')->post('/buku', [
            'kode' => 'BK001',
            'judul' => 'Duplikat Kode',
            'kategori_id' => $basisData->id,
        ]);
        $responseDup->assertRedirect('/buku/create')
            ->assertSessionHasErrors('kode');

        $this->assertDatabaseHas('bukus', [
            'kode' => 'BK001',
            'judul' => 'Dasar PHP',
        ]);

        // Judul kosong
        $responseEmpty = $this->from('/buku/create')->post('/buku', [
            'kode' => 'BK004',
            'judul' => '',
            'kategori_id' => $basisData->id,
        ]);
        $responseEmpty->assertRedirect('/buku/create')
            ->assertSessionHasErrors('judul');

        $this->assertDatabaseMissing('bukus', ['kode' => 'BK004']);
    }

    public function test_edit_update_dan_hapus_bk003(): void
    {
        $basisData = Kategori::where('nama', 'Basis Data')->firstOrFail();
        $buku = Buku::create([
            'kode' => 'BK003',
            'judul' => 'Pengantar SQL',
            'kategori_id' => $basisData->id,
        ]);

        // Edit form
        $this->get(route('buku.edit', $buku))
            ->assertStatus(200)
            ->assertSee('Ubah Buku')
            ->assertSee('BK003')
            ->assertSee('Pengantar SQL');

        // Update dengan kode tetap BK003 dan judul baru
        $this->put(route('buku.update', $buku), [
            'kode' => 'BK003',
            'judul' => 'Pengantar SQL Lanjut',
            'kategori_id' => $basisData->id,
        ])->assertRedirect(route('buku.index'))
            ->assertSessionHas('status', 'Buku diperbarui.');

        $this->assertDatabaseHas('bukus', [
            'id' => $buku->id,
            'kode' => 'BK003',
            'judul' => 'Pengantar SQL Lanjut',
        ]);

        // Hapus BK003
        $this->delete(route('buku.destroy', $buku))
            ->assertRedirect(route('buku.index'))
            ->assertSessionHas('status', 'Buku dihapus.');

        $this->assertDatabaseMissing('bukus', ['kode' => 'BK003']);
    }

    public function test_seeder_idempotent_dan_relasi_eloquent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('kategoris', 2);
        $this->assertDatabaseCount('bukus', 2);

        $pemrograman = Kategori::where('nama', 'Pemrograman')->firstOrFail();
        $this->assertCount(2, $pemrograman->bukus);
    }
}
