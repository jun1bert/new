<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ServiceManagementController;
use App\Http\Controllers\AppointmentManagementController;
use App\Http\Controllers\GalleryManagementController;
use App\Http\Controllers\ClientRecordController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\ProductManagementController;
use App\Http\Controllers\ClientClaimController;
use App\Http\Controllers\MemberAppointmentController;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\BusinessReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $galleryImages = \Illuminate\Support\Facades\Schema::hasTable('gallery_images')
        ? \App\Models\GalleryImage::query()
            ->where('is_published', true)
            ->latest()
            ->get()
            ->map(fn ($image) => (object) [
                'url' => asset($image->path),
                'title' => $image->title,
                'caption' => $image->caption,
                'path' => $image->path,
                'exists_on_disk' => file_exists(public_path($image->path)),
            ])
        : collect();

    return view('welcome', compact('galleryImages'));
});
Route::post('/appointments/request', [PublicBookingController::class, 'store'])->middleware('throttle:10,1')->name('appointments.request');
Route::get('/attendance/scan', [\App\Http\Controllers\AttendanceQrController::class, 'scan'])->middleware('throttle:30,1')->name('attendance.qr.scan');
Route::get('/attendance/confirm', [\App\Http\Controllers\AttendanceQrController::class, 'confirm'])->middleware(['auth', 'verified'])->name('attendance.qr.confirm');

Route::get('/dashboard', PortalController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/my-appointments/{appointment}/consent', [ConsentController::class, 'create'])->name('member.consents.create');
    Route::post('/my-appointments/{appointment}/consent', [ConsentController::class, 'store'])->middleware('throttle:10,1')->name('member.consents.store');
    Route::get('/consents/{consent}', [ConsentController::class, 'show'])->name('consents.show');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/claim-past-appointment', [ClientClaimController::class, 'create'])->name('claims.create');
    Route::post('/claim-past-appointment', [ClientClaimController::class, 'store'])->middleware('throttle:5,1')->name('claims.store');
    Route::get('/my-appointments/{appointment}', [MemberAppointmentController::class, 'show'])->name('member.appointments.show');
    Route::get('/my-appointments/{appointment}/receipt', [MemberAppointmentController::class, 'receipt'])->name('member.appointments.receipt');
    Route::get('/my-payments/{payment}/receipt', [MemberAppointmentController::class, 'paymentReceipt'])->name('member.payments.receipt');
});

Route::middleware(['auth', 'verified', 'administrator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/attendance', [\App\Http\Controllers\AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/qr', [\App\Http\Controllers\AttendanceQrController::class, 'display'])->name('attendance.qr.display');
    Route::get('/attendance/qr/current', [\App\Http\Controllers\AttendanceQrController::class, 'current'])->name('attendance.qr.current');
    Route::post('/attendance/corrections', [\App\Http\Controllers\AttendanceController::class, 'storeCorrection'])->name('attendance.corrections.store');
    Route::patch('/attendance/{attendance}/correction', [\App\Http\Controllers\AttendanceController::class, 'updateCorrection'])->name('attendance.corrections.update');
    Route::get('/settings', [\App\Http\Controllers\SchedulingController::class, 'settings'])->name('settings.index');
    Route::post('/appointments/{appointment}/services/{service}/skin-analysis', [\App\Http\Controllers\SkinAnalysisPhotoController::class, 'store'])->name('skin-analysis.store');
    Route::get('/skin-analysis/{photo}', [\App\Http\Controllers\SkinAnalysisPhotoController::class, 'show'])->name('skin-analysis.show');
    Route::delete('/skin-analysis/{photo}', [\App\Http\Controllers\SkinAnalysisPhotoController::class, 'destroy'])->name('skin-analysis.destroy');
    Route::post('/appointments/{appointment}/review-legacy-balance', [AppointmentManagementController::class, 'reviewLegacyBalance'])->name('appointments.review-legacy-balance');
    Route::put('/scheduling/hours', [\App\Http\Controllers\SchedulingController::class, 'updateHours'])->name('scheduling.hours');
    Route::get('/reports', BusinessReportController::class)->name('reports.index');
    Route::get('/consent-forms', [ConsentController::class, 'index'])->name('consents.index');
    Route::patch('/consent-forms/{service}', [ConsentController::class, 'update'])->name('consents.update');
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}/role', [UserManagementController::class, 'updateRole'])->name('users.role.update');
    Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::get('/claim-requests', [ClientClaimController::class, 'index'])->name('claims.index');
    Route::patch('/claim-requests/{claim}/approve', [ClientClaimController::class, 'approve'])->name('claims.approve');
    Route::patch('/claim-requests/{claim}/decline', [ClientClaimController::class, 'decline'])->name('claims.decline');
    Route::patch('/claim-requests/{claim}/invalidate', [ClientClaimController::class, 'invalidate'])->name('claims.invalidate');
    Route::get('/services', [ServiceManagementController::class, 'index'])->name('services.index');
    Route::post('/services', [ServiceManagementController::class, 'store'])->name('services.store');
    Route::patch('/services/{service}', [ServiceManagementController::class, 'update'])->name('services.update');
    Route::patch('/services/{service}/toggle', [ServiceManagementController::class, 'toggle'])->name('services.toggle');
    Route::get('/products', [ProductManagementController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductManagementController::class, 'show'])->name('products.show');
    Route::post('/products', [ProductManagementController::class, 'store'])->name('products.store');
    Route::patch('/products/{product}/stock', [ProductManagementController::class, 'adjust'])->name('products.stock.adjust');
});

Route::middleware(['auth', 'verified', 'studio-team'])->prefix('studio')->name('studio.')->group(function () {
    Route::post('/attendance/time-in', [\App\Http\Controllers\AttendanceController::class, 'clockIn'])->name('attendance.in');
    Route::post('/attendance/time-out', [\App\Http\Controllers\AttendanceController::class, 'clockOut'])->name('attendance.out');
    Route::get('/appointment-availability', [\App\Http\Controllers\SchedulingController::class, 'availability'])->name('appointments.availability');
    Route::get('/calendar', [\App\Http\Controllers\SchedulingController::class, 'index'])->name('calendar');
    Route::patch('/appointments/{appointment}/provider', [\App\Http\Controllers\SchedulingController::class, 'assign'])->name('appointments.provider');
    Route::patch('/appointments/{appointment}/reschedule', [\App\Http\Controllers\AppointmentLifecycleController::class, 'reschedule'])->name('appointments.reschedule');
    Route::patch('/appointments/{appointment}/cancel', [\App\Http\Controllers\AppointmentLifecycleController::class, 'cancel'])->name('appointments.cancel');
    Route::patch('/appointments/{appointment}/no-show', [\App\Http\Controllers\AppointmentLifecycleController::class, 'noShow'])->name('appointments.no-show');
    Route::post('/appointments/{appointment}/consent-tablet', [ConsentController::class, 'startTablet'])->name('consents.start');
    Route::get('/consent-tablet', [ConsentController::class, 'tablet'])->name('consents.tablet');
    Route::post('/consent-tablet/sign', [ConsentController::class, 'signTablet'])->middleware('throttle:10,1')->name('consents.sign');
    Route::post('/consent-tablet/unlock', [ConsentController::class, 'unlockTablet'])->middleware('throttle:5,1')->name('consents.unlock');
    Route::get('/clients', [ClientRecordController::class, 'index'])->name('clients.index');
    Route::get('/clients/{client}', [ClientRecordController::class, 'show'])->name('clients.show');
    Route::get('/appointments', [AppointmentManagementController::class, 'index'])->name('appointments.index');
    Route::post('/appointments', [AppointmentManagementController::class, 'store'])->name('appointments.store');
    Route::patch('/appointments/{appointment}/accept', [AppointmentManagementController::class, 'accept'])->name('appointments.accept');
    Route::patch('/appointments/{appointment}/decline', [AppointmentManagementController::class, 'decline'])->name('appointments.decline');
    Route::patch('/appointments/{appointment}/complete', [AppointmentManagementController::class, 'complete'])->name('appointments.complete');
    Route::post('/appointments/{appointment}/balance-payments', [AppointmentManagementController::class, 'payBalance'])->name('appointments.balance-payments');
    Route::get('/payments/{payment}/receipt', [AppointmentManagementController::class, 'receipt'])->name('payments.receipt');
    Route::post('/appointments/{appointment}/next-session', [AppointmentManagementController::class, 'scheduleNext'])->name('appointments.next-session');
    Route::get('/gallery', [GalleryManagementController::class, 'index'])->name('gallery.index');
    Route::post('/gallery', [GalleryManagementController::class, 'store'])->name('gallery.store');
    Route::patch('/gallery/{image}/toggle', [GalleryManagementController::class, 'toggle'])->name('gallery.toggle');
    Route::delete('/gallery/{image}', [GalleryManagementController::class, 'destroy'])->name('gallery.destroy');
});

require __DIR__.'/auth.php';

