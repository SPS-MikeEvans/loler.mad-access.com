<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyLiabilityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobPhotoController;
use App\Http\Controllers\JobSignatureController;
use App\Http\Controllers\KitItemController;
use App\Http\Controllers\KitTypeBulkEditController;
use App\Http\Controllers\KitTypeController;
use App\Http\Controllers\MobileInspectionController;
use App\Http\Controllers\PhotoCaptureController;
use App\Http\Controllers\Portal;
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

Route::middleware(['auth', 'verified', 'role:admin,inspector'])->group(function () {
    Route::resource('clients', ClientController::class);
    Route::post('/kit-types/ai-refresh', [KitTypeController::class, 'refresh'])
        ->name('kit-types.ai-refresh')
        ->middleware(['role:admin', 'throttle:5,60']);
    Route::resource('kit-types', KitTypeController::class)->except(['show']);

    Route::post('/kit-types/bulk-edit', [KitTypeBulkEditController::class, 'form'])->name('kit-types.bulk-edit.form');
    Route::post('/kit-types/bulk-edit/preview', [KitTypeBulkEditController::class, 'preview'])->name('kit-types.bulk-edit.preview');
    Route::post('/kit-types/bulk-edit/apply', [KitTypeBulkEditController::class, 'apply'])->name('kit-types.bulk-edit.apply');
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

    // AJAX: kit items for a client (used in job create/edit)
    Route::get('/api/clients/{client}/kit-items', [KitItemController::class, 'forClient'])->name('api.clients.kit-items');

    // Jobs (manifests / work orders)
    Route::resource('jobs', JobController::class);
    Route::post('jobs/{job}/kit-items/{kitItem}/mark-done', [JobController::class, 'markItemDone'])
        ->name('jobs.kit-items.mark-done');
    Route::get('jobs/{job}/bag-label', [JobController::class, 'bagLabel'])->name('jobs.bag-label');
    Route::get('jobs/{job}/sign-drop-off', [JobSignatureController::class, 'showDropOff'])->name('jobs.sign-drop-off');
    Route::post('jobs/{job}/sign-drop-off', [JobSignatureController::class, 'captureDropOff'])->name('jobs.capture-drop-off');
    Route::get('jobs/{job}/sign-return', [JobSignatureController::class, 'showReturn'])->name('jobs.sign-return');
    Route::post('jobs/{job}/sign-return', [JobSignatureController::class, 'captureReturn'])->name('jobs.capture-return');
    Route::post('jobs/{job}/photos', [JobPhotoController::class, 'store'])->name('jobs.photos.store');
    Route::delete('jobs/{job}/photos/{photo}', [JobPhotoController::class, 'destroy'])->name('jobs.photos.destroy');

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

Route::get('/bag/{bagQrCode}', [JobController::class, 'scanBag'])
    ->name('jobs.scan-bag')
    ->middleware('auth');

// Role-aware dispatcher used in notification emails — sends client_viewers to
// the portal and admin/inspector users to the back-office for the same job.
Route::get('/jobs/view/{job}', [JobController::class, 'viewDispatch'])
    ->name('jobs.view')
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

// Forced password change — available to all authenticated roles
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/password/change', [PasswordChangeController::class, 'create'])->name('password.change');
    Route::patch('/password/change', [PasswordChangeController::class, 'update'])->name('password.change.update');
});

// Client portal
Route::prefix('portal')
    ->middleware(['auth', 'verified', 'role:client_viewer', 'password.changed'])
    ->name('portal.')
    ->group(function () {
        Route::get('/', Portal\DashboardController::class)->name('dashboard');

        Route::get('/kit', [Portal\KitItemController::class, 'index'])->name('kit.index');
        Route::get('/kit/create', [Portal\KitItemController::class, 'create'])->name('kit.create');
        Route::post('/kit', [Portal\KitItemController::class, 'store'])->name('kit.store');
        Route::get('/kit/{kitItem}', [Portal\KitItemController::class, 'show'])->name('kit.show');
        Route::post('/kit/{kitItem}/flag', [Portal\KitItemController::class, 'storeFlag'])->name('kit.flag');
        Route::delete('/kit/{kitItem}/flag', [Portal\KitItemController::class, 'destroyFlag'])->name('kit.flag.destroy');
        Route::patch('/kit/{kitItem}/retire', [Portal\KitItemController::class, 'retire'])->name('kit.retire');
        Route::patch('/kit/{kitItem}/custom-name', [Portal\KitItemController::class, 'updateCustomName'])->name('kit.updateCustomName');

        Route::get('/kit/{kitItem}/edit', [Portal\KitItemController::class, 'edit'])->name('kit.edit');
        Route::patch('/kit/{kitItem}', [Portal\KitItemController::class, 'update'])->name('kit.update');

        Route::get('/kit/{kitItem}/inspections', [Portal\InspectionController::class, 'index'])->name('inspections.index');
        Route::get('/inspections/{inspection}/pdf', [Portal\InspectionController::class, 'downloadPdf'])->name('inspections.pdf');

        Route::resource('kit-groups', Portal\KitGroupController::class);

        Route::get('/jobs', [Portal\JobController::class, 'index'])->name('jobs.index');
        Route::get('/jobs/create', [Portal\JobController::class, 'create'])->name('jobs.create');
        Route::post('/jobs/create/date', [Portal\JobController::class, 'chooseDate'])->name('jobs.date');
        Route::post('/jobs/create/review', [Portal\JobController::class, 'review'])->name('jobs.review');
        Route::post('/jobs', [Portal\JobController::class, 'store'])->name('jobs.store');
        Route::get('/jobs/{job}', [Portal\JobController::class, 'show'])->name('jobs.show');
    });

require __DIR__.'/auth.php';
