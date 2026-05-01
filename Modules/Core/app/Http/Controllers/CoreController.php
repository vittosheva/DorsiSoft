<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class CoreController extends Controller
{
    public function index(): View
    {
        return view('core::index');
    }
}
