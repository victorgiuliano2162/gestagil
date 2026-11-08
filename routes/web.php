<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicReceiptController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/termos', function () {
    return Inertia::render('Legal/Terms');
})->name('terms');

Route::get('/privacidade', function () {
    return Inertia::render('Legal/Privacy');
})->name('privacy');

// Rotas públicas de verificação e download de recibos com rate-limiting
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/r/{code}', [PublicReceiptController::class, 'show'])->name('receipts.public.show');
    Route::get('/r/{code}/pdf', [PublicReceiptController::class, 'pdf'])->name('receipts.public.pdf');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/business-profile', [BusinessProfileController::class, 'edit'])->name('business-profile.edit');
    Route::post('/business-profile', [BusinessProfileController::class, 'update'])->name('business-profile.update');
    Route::get('/business-profile/logo', [BusinessProfileController::class, 'logo'])->name('business-profile.logo');

    Route::resource('clients', ClientController::class);

    Route::resource('appointments', AppointmentController::class);
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');

    Route::resource('transactions', TransactionController::class)->only(['index', 'create', 'store', 'destroy']);

    Route::resource('receipts', ReceiptController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/receipts/{receipt}/pdf', [ReceiptController::class, 'pdf'])->name('receipts.pdf');
    Route::post('/receipts/{receipt}/cancel', [ReceiptController::class, 'cancel'])->name('receipts.cancel');
    Route::post('/receipts/{receipt}/email', [ReceiptController::class, 'email'])
        ->middleware('throttle:10,60')
        ->name('receipts.email');

    Route::get('/relatorios', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
    Route::get('/backup/export', [BackupController::class, 'export'])->name('backup.export');
    Route::post('/backup/restore', [BackupController::class, 'restore'])->name('backup.restore');
});

require __DIR__.'/auth.php';
