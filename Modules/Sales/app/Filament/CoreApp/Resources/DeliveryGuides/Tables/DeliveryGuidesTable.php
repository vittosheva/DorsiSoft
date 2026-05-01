<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Support\Actions\GeneratePdfAction;
use Modules\Core\Support\Actions\GeneratePdfBulkAction;
use Modules\Core\Support\Actions\SendDocumentEmailAction;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Filters\DateRangeFilter;
use Modules\Core\Support\Tables\Filters\StatusFilter;
use Modules\Sales\Enums\DeliveryGuideStatusEnum;
use Modules\Sri\Support\Tables\Columns\CommercialStatusColumn;
use Modules\Sri\Support\Tables\Columns\ElectronicStatusColumn;

final class DeliveryGuidesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Delivery guides issued by this company to document the physical transfer of goods to customers or between locations. Each delivery guide is linked to an invoice or sales order and specifies the carrier, vehicle, recipient, and transport route. Delivery guides must be authorized by the SRI before the goods are transported.'))
            ->columns([
                CodeTextColumn::make('code')
                    ->sortable(),

                TextColumn::make('issue_date')
                    ->label(__('Issue Date'))
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('transport_start_date')
                    ->label(__('Transport Date'))
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('carrier_name')
                    ->label(__('Carrier'))
                    ->placeholder('—')
                    ->description(fn ($record) => $record->carrier_plate),

                TextColumn::make('recipients_count')
                    ->label(__('Recipients'))
                    ->counts('recipients')
                    ->alignCenter(),

                CommercialStatusColumn::make(),

                ElectronicStatusColumn::make(),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([
                DateRangeFilter::make('transport_start_date'),
                StatusFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn ($record) => $record->isEditable()),
                SendDocumentEmailAction::make(),
                GeneratePdfAction::make(),
                DeleteAction::make()
                    ->visible(fn ($record) => $record->status === DeliveryGuideStatusEnum::Draft),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    GeneratePdfBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
