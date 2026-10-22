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
Route::post('/appointments/request', [PublicBookingController::class, 'store'])->name('appointments.request');

Route::get('/dashboard', PortalController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/claim-past-appointment', [ClientClaimController::class, 'create'])->name('claims.create');
    Route::post('/claim-past-appointment', [ClientClaimController::class, 'store'])->name('claims.store');
    Route::get('/my-appointments/{appointment}', [MemberAppointmentController::class, 'show'])->name('member.appointments.show');
    Route::get('/my-appointments/{appointment}/receipt', [MemberAppointmentController::class, 'receipt'])->name('member.appointments.receipt');
});

Route::middleware(['auth', 'verified', 'administrator'])->prefix('admin')->name('admin.')->group(function () {
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
    Route::get('/clients', [ClientRecordController::class, 'index'])->name('clients.index');
    Route::get('/clients/{client}', [ClientRecordController::class, 'show'])->name('clients.show');
    Route::get('/appointments', [AppointmentManagementController::class, 'index'])->name('appointments.index');
    Route::post('/appointments', [AppointmentManagementController::class, 'store'])->name('appointments.store');
    Route::patch('/appointments/{appointment}/accept', [AppointmentManagementController::class, 'accept'])->name('appointments.accept');
    Route::patch('/appointments/{appointment}/decline', [AppointmentManagementController::class, 'decline'])->name('appointments.decline');
    Route::patch('/appointments/{appointment}/complete', [AppointmentManagementController::class, 'complete'])->name('appointments.complete');
    Route::get('/payments/{payment}/receipt', [AppointmentManagementController::class, 'receipt'])->name('payments.receipt');
    Route::post('/appointments/{appointment}/next-session', [AppointmentManagementController::class, 'scheduleNext'])->name('appointments.next-session');
    Route::get('/gallery', [GalleryManagementController::class, 'index'])->name('gallery.index');
    Route::post('/gallery', [GalleryManagementController::class, 'store'])->name('gallery.store');
    Route::patch('/gallery/{image}/toggle', [GalleryManagementController::class, 'toggle'])->name('gallery.toggle');
    Route::delete('/gallery/{image}', [GalleryManagementController::class, 'destroy'])->name('gallery.destroy');
});

require __DIR__.'/auth.php';
