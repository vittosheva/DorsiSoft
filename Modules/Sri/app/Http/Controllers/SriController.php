<?php

declare(strict_types=1);

namespace Modules\Sri\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class SriController extends Controller
{
    public function index(): View
    {
        return view('sri::index');
    }
}
