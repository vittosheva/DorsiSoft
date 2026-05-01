<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sri\Http\Controllers\SriController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('sris', SriController::class)
        ->only(['index'])
        ->names('sri');
});
