<?php

declare(strict_types=1);

namespace Modules\People\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class PeopleController extends Controller
{
    public function index(): View
    {
        return view('people::index');
    }
}
