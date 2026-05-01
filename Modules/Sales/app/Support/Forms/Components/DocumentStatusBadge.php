<?php

declare(strict_types=1);

namespace Modules\Sales\Support\Forms\Components;

use Closure;
use Filament\Schemas\Components\View;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Model;

final class DocumentStatusBadge
{
    public static function make(string $attribute = 'status', ?Closure $resolveStateUsing = null): Closure
    {
        return static function (?Model $record) use ($attribute, $resolveStateUsing): ?View {
            $state = $resolveStateUsing instanceof Closure
                ? $resolveStateUsing($record)
                : $record?->getAttributeValue($attribute);

            if ((! $state instanceof HasLabel) || (! $state instanceof HasColor)) {
                return null;
            }

            return View::make('sales::filament.components.document-status-badge')
                ->viewData([
                    'state' => $state,
                ]);
        };
    }
}
