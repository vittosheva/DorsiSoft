<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\People\Http\Controllers\PeopleController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('people', PeopleController::class)
        ->only(['index'])
        ->names('people');
});
