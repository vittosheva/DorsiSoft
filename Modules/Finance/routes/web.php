<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\FinanceController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('finances', FinanceController::class)
        ->only(['index'])
        ->names('finance');
});
