<?php
/**
 * Script untuk update nilai tampil_pada yang sudah ada di database
 * setelah migrasi add_tampil_pada_to_item_pemeriksaan_tables
 * 
 * Jalankan: php artisan tinker < database/update_tampil_pada.php
 * Atau: php database/update_tampil_pada.php (dari folder be)
 */

use Illuminate\Support\Facades\DB;

// Item Pemeriksaan Masuk
// SGM = hanya untuk unloading (truk membawa segel dari luar)
DB::table('item_pemeriksaan_masuks')->where('kode', 'SGM')->update(['tampil_pada' => 'unloading']);
// Sisanya semua
DB::table('item_pemeriksaan_masuks')->where('kode', '!=', 'SGM')->update(['tampil_pada' => 'semua']);

// Item Pemeriksaan Keluar  
// SGK = hanya untuk loading (truk keluar dengan segel pabrik)
DB::table('item_pemeriksaan_keluars')->where('kode', 'SGK')->update(['tampil_pada' => 'loading']);
// Sisanya semua
DB::table('item_pemeriksaan_keluars')->where('kode', '!=', 'SGK')->update(['tampil_pada' => 'semua']);

echo "tampil_pada berhasil diupdate!\n";
echo "SGM -> unloading\n";
echo "SGK -> loading\n";
echo "Lainnya -> semua\n";
