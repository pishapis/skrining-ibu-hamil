<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Master\FaskesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SkriningController;
use App\Http\Controllers\RiwayatSkriningController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Filter\FilterAlamatController;
use App\Http\Controllers\Master\PenggunaController;
use App\Http\Controllers\Master\JabatanController;
use App\Http\Controllers\Master\EducationContentController;
use App\Http\Controllers\Admin\RescreenTokenController;
use App\Http\Controllers\Admin\ScreeningBarcodeController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');
Route::view('/offline', 'offline')->name('offline');

Route::middleware('guest')->group(function () {
    // Tampilan login (default)
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::prefix('admin')->group(function (): void {
        Route::get('login', [AuthenticatedSessionController::class, 'login_admin'])
        ->name('login.admin.view');
    });

    // Login untuk user ibu (NIK only)
    Route::post('login/user', [AuthenticatedSessionController::class, 'storeUser'])
        ->name('login.user');

    // Login untuk admin/superadmin (email/username & password)
    Route::post('login/admin', [AuthenticatedSessionController::class, 'storeAdmin'])
        ->name('login.admin');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'anyrole:Admin,Superadmin'])->group(function () {
    Route::controller(PenggunaController::class)->group(function () {
        Route::get('manajemen-pengguna', 'index')->name('manajemen.pengguna');
        Route::post('pengguna-create-ibu', 'createIbu')->name('pengguna.ibu.create');
        Route::post('pengguna-update-bu', 'updateIbu')->name('pengguna.ibu.update');
        Route::post('pengguna-update-puskesmas', 'updatePuskesmas')->name('pengguna.puskesmas.update');
        Route::post('pengguna-create-puskesmas', 'createPuskesmas')->name('pengguna.puskesmas.create');
    });
});

Route::middleware(['auth', 'verified', 'is_superadmin'])->group(function () {

    Route::controller(JabatanController::class)->group(function () {
        Route::get('manajemen-jabatan', 'index')->name('manajemen.jabatan');
        Route::post('jabatan-update', 'update')->name('jabatan.update');
        Route::post('jabatan-create', 'store')->name('jabatan.store');
        Route::post('jabatan-delete', 'destroy')->name('jabatan.destroy');
    });

    Route::controller(FaskesController::class)->group(function () {
        Route::get('manajemen-faskes', 'index')->name('manajemen.faskes');
        Route::post('faskes-update-rujukan', 'updateRujukan')->name('faskes.rujukan.update');
        Route::post('faskes-update-puskesmas', 'updatePuskesmas')->name('faskes.puskesmas.update');
        Route::post('faskes-create-rujukan', 'createRujukan')->name('faskes.rujukan.create');
        Route::post('faskes-create-puskesmas', 'createPuskesmas')->name('faskes.puskesmas.create');
    });

    Route::get('/register-user',     [RegisteredUserController::class, 'create'])->name('register.user');

    Route::get('/edukasi-create',     [EducationContentController::class, 'create'])->name('edukasi.create');
    Route::post('/edukasi/post',            [EducationContentController::class, 'store'])->name('edukasi.store');
    Route::get('/edukasi/{slug}/edit', [EducationContentController::class, 'edit'])->name('edukasi.edit');
    Route::put('/edukasi/{slug}',      [EducationContentController::class, 'update'])->name('edukasi.update');
    Route::delete('/edukasi/{slug}',    [EducationContentController::class, 'destroy'])->name('edukasi.destroy');
});

Route::middleware(['auth', 'verified', 'is_admin', 'can:manage-rescreen-tokens'])->group(function () {
    Route::controller(RescreenTokenController::class)->group(function () {
        Route::get('rescreen-tokens', 'index')->name('rescreen.index');
        Route::post('rescreen-tokens', 'store')->name('admin.rescreens.store');
        Route::patch('/rescreen-tokens/{rescreen}/revoke', 'revoke')->name('admin.rescreens.revoke');
        Route::patch('/rescreen-tokens/{rescreen}/reactivate', 'reactivate')->name('admin.rescreens.reactivate');
    });

    Route::controller(ScreeningBarcodeController::class)->group(function () {
        Route::get('/generator', 'index')->name('generator');
        Route::post('/generate', 'generateLink')->name('skrining.generate');
        Route::get('/statistics', 'getStatistics')->name('skrining.statistics');
        Route::post('/link/deactivate/{id}', 'deactivateLink')->name('link.deactivate');
    });
});

Route::get('/s/{shortCode}', [ScreeningBarcodeController::class, 'redirectShort'])
    ->name('redirect');
Route::get('/form', [ScreeningBarcodeController::class, 'showForm'])
    ->name('form');

// Form submission (if needed)
Route::post('/submit', [ScreeningBarcodeController::class, 'submitForm'])
    ->name('submit');

Route::middleware(['auth', 'verified', 'is_user'])->group(function () {
    Route::controller(SkriningController::class)->group(function () {
        Route::get('skrining', 'index')->name('skrining.epds');
    });
});


Route::controller(FilterAlamatController::class)->group(function () {
    Route::get('get-kota', 'filter_kota')->name('kota.filter');
    Route::get('get-kecamatan', 'filter_kecamatan')->name('kecamatan.filter');
    Route::get('get-desa', 'filter_kel')->name('desa.filter');
    Route::get('get-faskes', 'filter_faskes')->name('faskes.filter');
});

Route::get('/skrining-umum', [SkriningController::class, 'skrining_umum'])->name('skrining.umum');
Route::middleware('auth')->group(function () {
    //skrining global 
    Route::post('/epds/cancel', [SkriningController::class, 'cancelEpds'])->name('epds.cancel');
    Route::get('/epds/start', [SkriningController::class, 'startEpds'])->name('epds.start');
    Route::post('/epds/save',  [SkriningController::class, 'saveEpdsAnswer'])->name('epds.save');
    Route::post('/epds/submit', [SkriningController::class, 'submitEpds'])->name('epds.submit');
    Route::get('/dass/start',  [SkriningController::class, 'startDass'])->name('dass.start');
    Route::post('/dass/answer', [SkriningController::class, 'saveDassAnswer'])->name('dass.save');
    Route::post('/dass/submit', [SkriningController::class, 'submitDass'])->name('dass.submit');
    Route::post('/dass/cancel', [SkriningController::class, 'cancelDass'])->name('dass.cancel');
    Route::post('first-create-usia-hamil', [SkriningController::class, 'first_create_usia_hamil'])->name('first.create.usia.hamil');
    Route::post('/update-data-diri', [SkriningController::class, 'updateDataDiri'])
        ->middleware('auth')
        ->name('update.data.diri');
        
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile-superadmin', [ProfileController::class, 'updateSuperadmin'])->name('profile.update.superadmin');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/edukasi',               [EducationContentController::class, 'index'])->name('edukasi.index');
    Route::get('/edukasi/{slug}',        [EducationContentController::class, 'show'])->name('edukasi.show');

    Route::controller(RiwayatSkriningController::class)->group(function () {
        Route::get('riwayat-skrining', 'index')->name('riwayat.skrining');
    });
});

Route::get('/erlog', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
require __DIR__ . '/auth.php';
