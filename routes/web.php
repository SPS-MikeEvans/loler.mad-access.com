<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyLiabilityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\KitItemController;
use App\Http\Controllers\KitTypeController;
use App\Http\Controllers\MobileInspectionController;
use App\Http\Controllers\PhotoCaptureController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/liabilities', [CompanyLiabilityController::class, 'showPublic'])
    ->name('liabilities.public');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('clients', ClientController::class);
    Route::resource('kit-types', KitTypeController::class)->except(['show']);
    Route::resource('clients.kit-items', KitItemController::class)->scoped();
    Route::resource('clients.kit-items.inspections', InspectionController::class)
        ->scoped()
        ->only(['index', 'create', 'store', 'show']);

    Route::patch('/inspections/{inspection}/cost', [InspectionController::class, 'updateCost'])
        ->name('inspections.update-cost')
        ->middleware('role:admin');

    Route::get('/inspections/{inspection}/pdf', [InspectionController::class, 'downloadPdf'])
        ->name('inspections.pdf')
        ->can('view-reports');

    Route::get('/clients/{client}/reports/inspections', [ReportController::class, 'clientInspections'])
        ->name('clients.reports.inspections')
        ->can('view-reports');

    Route::resource('clients.invoices', InvoiceController::class)
        ->scoped()
        ->only(['create', 'store', 'show', 'destroy'])
        ->middleware('role:admin');

    Route::get('/clients/{client}/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])
        ->name('clients.invoices.pdf')
        ->can('view-reports');

    Route::resource('users', UserController::class)->middleware('role:admin');

    Route::get('/audit-log', [AuditLogController::class, 'index'])
        ->name('audit-log.index')
        ->middleware('role:admin');

    Route::get('/liabilities/manage', [CompanyLiabilityController::class, 'edit'])
        ->name('liabilities.edit')
        ->middleware('role:admin');
    Route::put('/liabilities/manage', [CompanyLiabilityController::class, 'update'])
        ->name('liabilities.update')
        ->middleware('role:admin');
});

// Phone-as-camera: token-authenticated, no session required
Route::get('/photo-capture/{token}', [PhotoCaptureController::class, 'show'])->name('photo-capture.show');
Route::post('/photo-capture/{token}', [PhotoCaptureController::class, 'upload'])->name('photo-capture.upload');
Route::get('/photo-capture/{token}/status', [PhotoCaptureController::class, 'status'])->name('photo-capture.status');
Route::get('/photo-capture/{token}/qr', [PhotoCaptureController::class, 'qrCode'])->name('photo-capture.qr');

Route::get('/inspect/{qrCode}', [MobileInspectionController::class, 'scanStart'])
    ->name('inspect.qr')
    ->middleware('auth');

Route::prefix('mobile/inspections')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/{kitItem}/start', [MobileInspectionController::class, 'start'])
        ->name('mobile.inspect.start');
    Route::post('/{kitItem}/create-draft', [MobileInspectionController::class, 'createDraft'])
        ->name('mobile.inspect.create-draft');
    Route::get('/{inspection}/wizard/{checkIndex}', [MobileInspectionController::class, 'wizard'])
        ->name('mobile.inspect.wizard');
    Route::post('/{inspection}/save-check', [MobileInspectionController::class, 'saveCheck'])
        ->name('mobile.inspect.save-check');
    Route::post('/{inspection}/upload-photo/{check}', [MobileInspectionController::class, 'uploadPhoto'])
        ->name('mobile.inspect.upload-photo');
    Route::delete('/{inspection}/delete-photo/{photo}', [MobileInspectionController::class, 'deletePhoto'])
        ->name('mobile.inspect.delete-photo');
    Route::get('/{inspection}/complete', [MobileInspectionController::class, 'completeScreen'])
        ->name('mobile.inspect.complete-screen');
    Route::post('/{inspection}/complete', [MobileInspectionController::class, 'complete'])
        ->name('mobile.inspect.complete');
    Route::get('/{inspection}/done', [MobileInspectionController::class, 'done'])
        ->name('mobile.inspect.done');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
