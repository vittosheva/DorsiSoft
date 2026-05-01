<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class InventoryController extends Controller
{
    public function index(): View
    {
        return view('inventory::index');
    }
}
