<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class FinanceController extends Controller
{
    public function index(): View
    {
        return view('finance::index');
    }
}
