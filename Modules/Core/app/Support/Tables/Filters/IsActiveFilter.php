<?php

declare(strict_types=1);

namespace Modules\Core\Support\Tables\Filters;

use Filament\Tables\Filters\TernaryFilter;

final class IsActiveFilter extends TernaryFilter
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Is active'))
            ->trueLabel(__('Active'))
            ->falseLabel(__('Inactive'))
            ->boolean()
            ->native(false);
    }

    public static function getDefaultName(): ?string
    {
        return 'is_active';
    }
}
