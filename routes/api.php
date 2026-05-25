<?php

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\API\AuthController;

// Master
use App\Http\Controllers\API\Master\TransporterController;
use App\Http\Controllers\API\Master\DriverController;
use App\Http\Controllers\API\Master\JenisKendaraanController;
use App\Http\Controllers\API\Master\LogistikController;
use App\Http\Controllers\API\Master\ProdukController;
use App\Http\Controllers\API\Master\UserController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\Master\ItemKelengkapanSupirController;
use App\Http\Controllers\API\Master\ItemMuatanController;
use App\Http\Controllers\API\Master\ItemPemeriksaanMasukController;
use App\Http\Controllers\API\Master\ItemPemeriksaanKeluarController;

// VCF
use App\Http\Controllers\API\VCF\VcfBagian1Controller;
use App\Http\Controllers\API\VCF\VcfBagian2Controller;
use App\Http\Controllers\API\VCF\VcfBagian3Controller;
use App\Http\Controllers\API\VCF\VcfBagian4Controller;
use App\Http\Controllers\API\VCF\VcfSegelController;
use App\Http\Controllers\API\TimbanganController;

// Settings
use App\Http\Controllers\API\SettingController;

// Activity Logs
use App\Http\Controllers\API\ActivityLogController;
use App\Http\Controllers\API\Master\ViolationController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::options('login', function () {
    return response()->json([], 200);
});
Route::post('login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth Actions
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::get('dashboard/stats', [DashboardController::class, 'getStats']);

    /*
    Master Data Routes
    */
    Route::prefix('master')->group(function () {
        
        // --- MASTER RESOURCES (Singular & Plural) ---
        $masterResources = [
            'transporter'            => [TransporterController::class, 'transporter'],
            'transporters'           => [TransporterController::class, 'transporter'], // Alias
            'driver'                 => [DriverController::class, 'driver'],
            'drivers'                => [DriverController::class, 'driver'], // Alias
            'logistik'               => [LogistikController::class, 'logistik'],
            'produk'                 => [ProdukController::class, 'produk'],
            'jenis-kendaraan'        => [JenisKendaraanController::class, 'jenisKendaraan'],
            'item-kelengkapan-supir' => [ItemKelengkapanSupirController::class, 'itemKelengkapanSupir'],
            'item-muatan'            => [ItemMuatanController::class, 'itemMuatan'],
            'item-pemeriksaan-masuk' => [ItemPemeriksaanMasukController::class, 'itemPemeriksaanMasuk'],
            'item-pemeriksaan-keluar' => [ItemPemeriksaanKeluarController::class, 'itemPemeriksaanKeluar'],
            'user'                   => [UserController::class, 'user'],
            'users'                  => [UserController::class, 'user'], // Alias
        ];

        foreach ($masterResources as $uri => $config) {
            $controller = $config[0];
            $param = $config[1];

            // List & Show (Petugas & Admin)
            Route::middleware('petugas')->group(function () use ($uri, $controller, $param) {
                Route::get($uri, [$controller, 'index']);
                Route::get($uri . '/{' . $param . '}', [$controller, 'show']);
            });

            // CRUD (Admin Only)
            Route::middleware('admin')->group(function () use ($uri, $controller, $param) {
                Route::post($uri, [$controller, 'store']);
                Route::match(['put', 'patch'], $uri . '/{' . $param . '}', [$controller, 'update']);
                Route::delete($uri . '/{' . $param . '}', [$controller, 'destroy']);
            });
        }

        // Special Admin Routes
        Route::middleware('admin')->group(function () {
            Route::post('user/{user}/reset-password', [UserController::class, 'resetPassword']);
            Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword']);
        });

        // Violations
        Route::middleware('petugas')->group(function () {
            Route::get('violations/check', [ViolationController::class, 'check']);
        });
        Route::middleware('admin')->group(function () {
            Route::get('violations', [ViolationController::class, 'index']);
            Route::post('violations', [ViolationController::class, 'store']);
            Route::put('violations/{violation}', [ViolationController::class, 'update']);
            Route::delete('violations/{violation}', [ViolationController::class, 'destroy']);
            Route::patch('driver/{driver}/status', [ViolationController::class, 'updateDriverStatus']);
            Route::patch('drivers/{driver}/status', [ViolationController::class, 'updateDriverStatus']);
        });
    });

    /*
    |----------------------------------------------------------------------
    | VCF (Vehicle Check Form) Routes
    |----------------------------------------------------------------------
    | Multistep process for Petugas
    */
    Route::prefix('vcf')->group(function () {
        
        Route::middleware('petugas')->group(function () {
            // Bagian 1 (Main Gate Masuk)
            Route::get('next-number', [VcfBagian1Controller::class, 'getNextNumber']);
            Route::get('/', [VcfBagian1Controller::class, 'index']);
            Route::post('/', [VcfBagian1Controller::class, 'store']);
            Route::get('{id}', [VcfBagian1Controller::class, 'show']);
            Route::put('{id}', [VcfBagian1Controller::class, 'update']);
            Route::post('{id}/reject', [VcfBagian1Controller::class, 'reject']);

            // Bagian 2 (Weighbridge Masuk)
            Route::get('{vcfId}/bagian2', [VcfBagian2Controller::class, 'show']);
            Route::post('{vcfId}/bagian2', [VcfBagian2Controller::class, 'store']);
            Route::post('{vcfId}/bagian2/reject', [VcfBagian2Controller::class, 'reject']);
            Route::put('{vcfId}/bagian2', [VcfBagian2Controller::class, 'update']);


            // Bagian 3 (Weighbridge Keluar)
            Route::get('{vcfId}/bagian3', [VcfBagian3Controller::class, 'show']);
            Route::post('{vcfId}/bagian3', [VcfBagian3Controller::class, 'store']);
            Route::post('{vcfId}/bagian3/reject', [VcfBagian3Controller::class, 'reject']);
            Route::put('{vcfId}/bagian3', [VcfBagian3Controller::class, 'update']);

            // Bagian 4 (Main Gate Keluar)
            Route::get('{vcfId}/bagian4', [VcfBagian4Controller::class, 'show']);
            Route::post('{vcfId}/bagian4', [VcfBagian4Controller::class, 'store']);
            Route::put('{vcfId}/bagian4', [VcfBagian4Controller::class, 'update']);
            Route::post('{vcfId}/finalize', [VcfBagian4Controller::class, 'finalize']);

            // Segel (standalone endpoints)
            Route::post('{vcfId}/segel-masuk', [VcfSegelController::class, 'storeMasuk']);
            Route::post('{vcfId}/segel-keluar', [VcfSegelController::class, 'storeKeluar']);

            // Timbangan
            Route::post('timbangan', [TimbanganController::class, 'store']);
            Route::get('{vcfId}/timbangan', [TimbanganController::class, 'show']);
            Route::post('{vcfId}/timbangan/bruto', [TimbanganController::class, 'updateBruto']);
            Route::post('{vcfId}/timbangan/tara', [TimbanganController::class, 'updateTara']);
            Route::put('{vcfId}/timbangan/petugas', [TimbanganController::class, 'updatePetugas']);
            Route::put('{vcfId}/timbangan/admin', [TimbanganController::class, 'updateAdmin'])->middleware('admin');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Activity Log Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('activity-logs')->middleware('petugas')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index']);
        Route::get('/stats', [ActivityLogController::class, 'stats']);
        Route::get('/{id}', [ActivityLogController::class, 'show']);
    });

    /*
    |--------------------------------------------------------------------------
    | Settings Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')->middleware('petugas')->group(function () {
        Route::get('/', [SettingController::class, 'index']);
        Route::get('/public', [SettingController::class, 'public']);
        Route::get('/vcf', [SettingController::class, 'vcfSettings']);
        Route::get('/print', [SettingController::class, 'printSettings']);
        Route::get('/{key}', [SettingController::class, 'show']);
        
        // Admin only
        Route::middleware('admin')->group(function () {
            Route::put('/batch', [SettingController::class, 'updateBatch']);
            Route::put('/{key}', [SettingController::class, 'update']);
        });
    });
});
