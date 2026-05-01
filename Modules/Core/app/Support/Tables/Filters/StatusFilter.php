<?php

declare(strict_types=1);

namespace Modules\Core\Support\Tables\Filters;

use Filament\Tables\Filters\SelectFilter;

final class StatusFilter extends SelectFilter
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Status'))
            ->options([
                1 => __('Active'),
                0 => __('Inactive'),
            ])
            ->native(false);
    }

    public static function getDefaultName(): ?string
    {
        return 'status';
    }
}
