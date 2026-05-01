<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Sales\Events\SaleConfirmed;

final class SalesController extends Controller
{
    public function index(): View
    {
        return view('sales::index');
    }

    public function confirm(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'min:1'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        SaleConfirmed::dispatch(
            (int) $validated['company_id'],
            $id,
            $validated['items'],
        );

        return response()->json([
            'status' => 'ok',
            'message' => 'Sale confirmation event dispatched.',
            'sale_id' => $id,
        ]);
    }
}
