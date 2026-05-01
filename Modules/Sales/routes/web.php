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

// ── SRI v2: public signed URLs (no session auth required) ───────────────────
Route::middleware('signed')->group(function (): void {
    Route::get('/v2/ride/{type}/{id}', [SriRidePdfController::class, 'view'])
        ->name('sales.v2.ride.view');

    Route::get('/v2/xml/{type}/{id}/download', [SriXmlController::class, 'download'])
        ->name('sales.v2.xml.download');
});
