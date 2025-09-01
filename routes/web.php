<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SkriningController;
use App\Http\Controllers\RiwayatSkriningController;
use App\Http\Controllers\Filter\FilterAlamatController;
use App\Http\Controllers\Master\PenggunaController;
use App\Http\Controllers\Admin\RescreenTokenController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'is_superadmin'])->group(function () {

    Route::controller(PenggunaController::class)->group(function () {
        Route::get('manajemen-pengguna', 'index')->name('manajemen.pengguna');
    });

});

Route::middleware(['auth', 'verified', 'is_admin'])->group(function () {
    //
});

Route::middleware(['auth','can:manage-rescreen-tokens'])->prefix('admin/rescreens')->name('admin.rescreens.')->group(function () {
    Route::get('/', [RescreenTokenController::class, 'index'])->name('index');
    Route::post('/', [RescreenTokenController::class, 'store'])->name('store');
    Route::patch('/{rescreen}/revoke', [RescreenTokenController::class, 'revoke'])->name('revoke');
    Route::patch('/{rescreen}/reactivate', [RescreenTokenController::class, 'reactivate'])->name('reactivate');
});

Route::middleware(['auth', 'verified', 'is_user'])->group(function () {
    Route::controller(SkriningController::class)->group(function () {
        Route::get('skrining', 'index')->name('skrining.epds');
        Route::post('/epds/cancel', [SkriningController::class, 'cancelEpds'])->name('epds.cancel');
        Route::get('/epds/start', [SkriningController::class,'startEpds'])->name('epds.start');
        Route::post('/epds/save',  [SkriningController::class,'saveEpdsAnswer'])->name('epds.save');
        Route::post('/epds/submit',[SkriningController::class,'submitEpds'])->name('epds.submit');

        Route::get ('/dass/start',  [SkriningController::class,'startDass'])->name('dass.start');
        Route::post('/dass/answer', [SkriningController::class,'saveDassAnswer'])->name('dass.save');
        Route::post('/dass/submit', [SkriningController::class,'submitDass'])->name('dass.submit');
        Route::post('/dass/cancel', [SkriningController::class,'cancelDass'])->name('dass.cancel');
        Route::post('first-create-usia-hamil', 'first_create_usia_hamil')->name('first.create.usia.hamil');
    });

    Route::controller(RiwayatSkriningController::class)->group(function () {
        
    });
});

Route::controller(FilterAlamatController::class)->group(function () {
    Route::get('get-kota', 'filter_kota')->name('kota.filter');
    Route::get('get-kecamatan', 'filter_kecamatan')->name('kecamatan.filter');
    Route::get('get-desa', 'filter_kel')->name('desa.filter');
    Route::get('get-faskes', 'filter_faskes')->name('faskes.filter');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/erlog', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
require __DIR__ . '/auth.php';
