<?php

declare(strict_types=1);

namespace Modules\Core\Support\Tables\Columns;

use Filament\Tables\Columns\TextColumn;
use Modules\Core\Support\Audit\AuditDisplayDataResolver;

final class CreatedByTextColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Created by'))
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true)
            ->state(fn ($record): string => app(AuditDisplayDataResolver::class)->resolveCreatorName($record));
    }

    public static function getDefaultName(): ?string
    {
        return 'creator';
    }
}
