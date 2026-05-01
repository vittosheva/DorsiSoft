<?php

declare(strict_types=1);

namespace Modules\Core\Support\Pdf;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class PdfDateFormatter
{
    public static function formatDate(mixed $value): string
    {
        if (blank($value)) {
            return '—';
        }

        $date = $value instanceof CarbonInterface
            ? $value
            : Carbon::parse($value);

        return $date
            ->timezone((string) config('app.timezone'))
            ->translatedFormat('d/m/Y');
    }

    public static function formatDateTime(mixed $value): string
    {
        if (blank($value)) {
            return '—';
        }

        $date = $value instanceof CarbonInterface
            ? $value
            : Carbon::parse($value);

        return $date
            ->timezone((string) config('app.timezone'))
            ->translatedFormat('d/m/Y H:i');
    }
}
