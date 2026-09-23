<?php

use App\Http\Controllers\Admin\BillingController as AdminBillingController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\PackageController as AdminPackageController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SalesReportController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Customer\AppointmentController;
use App\Http\Controllers\Payments\PaymentCallbackController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\Pos\StaffConfirmPaymentController;
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
    $user = auth()->user();
    if ($user) {
        $home = $user->homeRoute();
        if ($home !== 'dashboard') {
            return redirect()->route($home);
        }
    }

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

Route::middleware(['auth', 'role:owner'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => Inertia::render('Admin/Dashboard'))->name('dashboard');

    Route::resource('services', AdminServiceController::class)->except(['show']);
    Route::resource('packages', AdminPackageController::class)->except(['show']);
    Route::resource('products', AdminProductController::class)->except(['show']);
    Route::post('products/{product}/stock', [AdminProductController::class, 'adjustStock'])
        ->name('products.stock');

    Route::get('staff', [AdminStaffController::class, 'index'])->name('staff.index');
    Route::post('staff', [AdminStaffController::class, 'store'])->name('staff.store');

    Route::get('bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
    Route::post('bookings/{appointment}/status', [AdminBookingController::class, 'updateStatus'])
        ->name('bookings.status');

    Route::get('billing', [AdminBillingController::class, 'index'])->name('billing.index');
    Route::get('billing/{sale}/invoice', [AdminBillingController::class, 'invoice'])
        ->name('billing.invoice');
    Route::post('billing/{sale}/mark-paid', [AdminBillingController::class, 'markPaid'])
        ->name('billing.mark-paid');

    Route::get('sales', [SalesReportController::class, 'index'])->name('sales.index');

    Route::get('settings', [AdminSettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [AdminSettingController::class, 'update'])->name('settings.update');
});

Route::match(['GET', 'POST'], '/payments/{method}/callback', [PaymentCallbackController::class, 'handle'])
    ->name('payments.callback');

Route::middleware(['auth', 'role:staff,owner'])->group(function () {
    Route::get('/staff/today', [TodayController::class, 'index'])->name('staff.today');

    Route::get('/staff/billing', [AdminBillingController::class, 'index'])->name('staff.billing.index');
    Route::get('/staff/billing/{sale}/invoice', [AdminBillingController::class, 'invoice'])
        ->name('staff.billing.invoice');
    Route::post('/staff/billing/{sale}/mark-paid', [AdminBillingController::class, 'markPaid'])
        ->name('staff.billing.mark-paid');

    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::get('/customers', [PosController::class, 'searchCustomers'])->name('customers');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
        Route::post('/sales/{sale}/void', [PosController::class, 'void'])->name('sales.void');
        Route::post('/sales/{sale}/staff-confirm', [StaffConfirmPaymentController::class, 'store'])
            ->name('payments.staff-confirm');
    });
});

require __DIR__.'/auth.php';
