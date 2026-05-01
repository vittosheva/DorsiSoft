<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\Collections\RelationManagers;

use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Support\Actions\GeneratePdfAction;
use Modules\Core\Support\Pdf\PdfDocumentRouteKey;
use Modules\Core\Support\RelationManagers\BaseRelationManager;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Finance\Models\CollectionAllocationReversal;

final class CollectionReversalsRelationManager extends BaseRelationManager
{
    protected static string $relationship = 'allocationReversals';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Reversals');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(__('Reversal entries that cancel previously applied allocations for this collection. A reversal restores the affected invoice\'s outstanding balance and returns the amount to the collection\'s available balance. Reversals create an immutable audit trail of all allocation corrections.'))
            // ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('creator:id,name,avatar_url'))
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('reversed_at')
                    ->label(__('Date'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('invoice.code')
                    ->label(__('Invoice'))
                    ->weight(FontWeight::Medium),

                MoneyTextColumn::make('reversed_amount')
                    ->currencyCode(fn (): string => $this->getOwnerRecord()->currency_code ?? 'USD'),

                TextColumn::make('reversal_type')
                    ->label(__('Type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        CollectionAllocationReversal::TYPE_FULL => __('Full'),
                        CollectionAllocationReversal::TYPE_PARTIAL => __('Partial'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        CollectionAllocationReversal::TYPE_FULL => 'danger',
                        CollectionAllocationReversal::TYPE_PARTIAL => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('reason')
                    ->limit(60)
                    ->tooltip(fn (CollectionAllocationReversal $record): string => $record->reason),

                TextColumn::make('reversedBy.name')
                    ->label(__('Reversed By'))
                    ->placeholder('—'),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([
                GeneratePdfAction::make(),

                Action::make('downloadReceipt')
                    ->tooltip(__('Receipt'))
                    ->icon(Heroicon::DocumentArrowDown)
                    ->color('gray')
                    ->url(fn (CollectionAllocationReversal $record): string => route('core.pdf.download', [
                        'model' => PdfDocumentRouteKey::fromClass(CollectionAllocationReversal::class),
                        'id' => $record->getKey(),
                    ]))
                    ->openUrlInNewTab()
                    ->disabled(fn (CollectionAllocationReversal $record): bool => blank($record->metadata['pdf_path'] ?? null))
                    ->tooltip(fn (CollectionAllocationReversal $record): ?string => blank($record->metadata['pdf_path'] ?? null)
                        ? __('Generate receipt first')
                        : null),
            ])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc');
    }
}
