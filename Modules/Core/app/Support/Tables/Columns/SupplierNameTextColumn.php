<?php

declare(strict_types=1);

namespace Modules\Core\Support\Tables\Columns;

use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

final class SupplierNameTextColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->placeholder('—')
            ->description(fn (Model $record) => $record->supplier_identification)
            ->weight(FontWeight::Bold)
            ->sortable()
            ->searchable();
    }

    public static function getDefaultName(): ?string
    {
        return 'supplier_name';
    }
}
