<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class AccountingController extends Controller
{
    public function index(): View
    {
        return view('accounting::index');
    }
}
