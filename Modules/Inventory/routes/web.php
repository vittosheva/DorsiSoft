<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\InventoryController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('inventories', InventoryController::class)
        ->only(['index'])
        ->names('inventory');
});
