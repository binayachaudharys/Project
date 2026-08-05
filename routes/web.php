<?php

use App\Http\Controllers\Customer\AppointmentController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PackageCatalogController;
use App\Http\Controllers\Public\ServiceCatalogController;
use App\Http\Controllers\Staff\TodayController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/services', [ServiceCatalogController::class, 'index'])->name('services.index');
Route::get('/packages', [PackageCatalogController::class, 'index'])->name('packages.index');
Route::get('/packages/{package}', [PackageCatalogController::class, 'show'])->name('packages.show');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/book', [AppointmentController::class, 'create'])->name('book.create');
    Route::post('/book', [AppointmentController::class, 'store'])->name('book.store');

    Route::get('/account/appointments', [AppointmentController::class, 'index'])->name('account.appointments');
    Route::delete('/account/appointments/{appointment}', [AppointmentController::class, 'cancel'])->name('account.appointments.cancel');
});

Route::middleware(['auth', 'role:owner'])->prefix('admin')->group(function () {
    Route::get('/', fn () => Inertia::render('Admin/Dashboard'))->name('admin.dashboard');
});

Route::middleware(['auth', 'role:staff,owner'])->group(function () {
    Route::get('/staff/today', [TodayController::class, 'index'])->name('staff.today');

    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::get('/customers', [PosController::class, 'searchCustomers'])->name('customers');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
        Route::post('/sales/{sale}/void', [PosController::class, 'void'])->name('sales.void');
    });
});

require __DIR__.'/auth.php';
