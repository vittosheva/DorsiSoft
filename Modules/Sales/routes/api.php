<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\SalesController;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::post('sales/{sale}/confirm', [SalesController::class, 'confirm'])->name('sales.confirm');
});
