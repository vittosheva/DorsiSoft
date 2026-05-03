<?php

declare(strict_types=1);

namespace Modules\Core\Support\Forms\Textareas;

use Filament\Forms\Components\Textarea;

final class NotesTextarea extends Textarea
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->hiddenLabel()
            ->rows(3)
            ->nullable()
            ->columnSpan(6);
    }

    public static function getDefaultName(): ?string
    {
        return 'notes';
    }
}
