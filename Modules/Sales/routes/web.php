<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\SalesController;
use Modules\Sales\Http\Controllers\SriRidePdfController;
use Modules\Sales\Http\Controllers\SriXmlController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('sales', SalesController::class)
        ->only(['index'])
        ->names('sales');
});

// ── SRI v1: public signed URLs (no session auth required) ───────────────────
Route::middleware('signed')->group(function (): void {
    Route::get('/v1/ride/{type}/{id}', [SriRidePdfController::class, 'view'])
        ->name('sales.v1.ride.view');

    Route::get('/v1/xml/{type}/{id}/download', [SriXmlController::class, 'download'])
        ->name('sales.v1.xml.download');
});
