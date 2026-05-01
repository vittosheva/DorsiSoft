<?php

declare(strict_types=1);

namespace Modules\Core\Support\Components\Sections;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Operation;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Support\Audit\AuditDisplayDataResolver;

final class AuditSection extends Section
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->heading(__('Audit'))
            ->icon(Heroicon::ShieldCheck)
            ->schema([
                TextEntry::make('audit_creator_name')
                    ->label(__('Created by'))
                    ->state(fn (?Model $record): string => app(AuditDisplayDataResolver::class)->resolveCreatorName($record))
                    ->inlineLabel(),
                TextEntry::make('audit_created_at')
                    ->label(__('Creation date'))
                    ->state(fn (?Model $record): string => app(AuditDisplayDataResolver::class)->resolveCreatedAt($record))
                    ->inlineLabel(),
                TextEntry::make('audit_editor_name')
                    ->label(__('Modified by'))
                    ->state(fn (?Model $record): string => app(AuditDisplayDataResolver::class)->resolveEditorName($record))
                    ->inlineLabel(),
                TextEntry::make('audit_updated_at')
                    ->label(__('Modification date'))
                    ->state(fn (?Model $record): string => app(AuditDisplayDataResolver::class)->resolveUpdatedAt($record))
                    ->inlineLabel(),
            ])
            ->visibleOn([
                Operation::Edit,
                Operation::View,
            ])
            ->columnSpanFull();
    }

    public static function getDefaultName(): ?string
    {
        return 'audit';
    }
}
