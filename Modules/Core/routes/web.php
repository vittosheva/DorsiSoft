<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CoreController;
use Modules\Core\Http\Controllers\PdfDownloadController;

Route::middleware('signed')->group(function () {
    Route::get('/pdf/share/{pdfShareLink}', [PdfDownloadController::class, 'viewShared'])
        ->name('core.pdf.share.view');
    Route::get('/pdf/share/{pdfShareLink}/download', [PdfDownloadController::class, 'downloadShared'])
        ->name('core.pdf.share.download');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cores', CoreController::class)
        ->only(['index'])
        ->names('core');

    Route::get('/pdf/{model}/{id}/view', [PdfDownloadController::class, 'view'])
        ->name('core.pdf.view')
        ->whereNumber('id');

    Route::get('/pdf/{model}/{id}', [PdfDownloadController::class, 'download'])
        ->name('core.pdf.download')
        ->whereNumber('id');
});
