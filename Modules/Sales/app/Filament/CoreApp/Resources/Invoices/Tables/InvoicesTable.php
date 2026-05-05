<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Invoices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Support\Actions\GeneratePdfAction;
use Modules\Core\Support\Actions\GeneratePdfBulkAction;
use Modules\Core\Support\Actions\SendDocumentEmailAction;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\CustomerNameTextColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Core\Support\Tables\Filters\DateRangeFilter;
use Modules\Core\Support\Tables\Filters\StatusFilter;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Support\Tables\Columns\SriSequentialTextColumn;
use Modules\Sales\Support\Tables\Filters\CustomerFilter;
use Modules\Sales\Support\Tables\Filters\SellerFilter;
use Modules\Sales\Support\Tables\Filters\VoidedFilter;
use Modules\Sri\Support\Tables\Columns\CommercialStatusColumn;
use Modules\Sri\Support\Tables\Columns\ElectronicStatusColumn;
use Modules\Workflow\Support\Tables\Columns\ApprovalDecisionTextColumn;

final class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Sales invoices issued by this company to customers. Each invoice goes through electronic authorization with the SRI before it is considered legally valid. Invoices can generate associated credit notes, debit notes, withholdings, and delivery guides throughout their lifecycle.'))
            ->columns([
                CodeTextColumn::make('code')
                    ->sortable(),

                SriSequentialTextColumn::make(),

                TextColumn::make('issue_date')
                    ->label(__('Issue date'))
                    ->date('d/m/Y')
                    ->sortable(),

                CustomerNameTextColumn::make('customer_name'),

                TextColumn::make('seller_name')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('salesOrder.code')
                    ->placeholder('—'),

                MoneyTextColumn::make('total')
                    ->currencyCode(fn(?Invoice $record): string => $record?->currency_code ?? ''),

                MoneyTextColumn::make('paid_amount')
                    ->label(__('Paid'))
                    ->currencyCode(fn(?Invoice $record): string => $record?->currency_code ?? '')
                    ->color(fn(?Invoice $record) => match ($record?->paymentStatus()) {
                        'paid' => 'success',
                        'partially_paid' => 'warning',
                        default => 'gray',
                    }),

                CommercialStatusColumn::make()
                    ->alignment(Alignment::Center),

                ElectronicStatusColumn::make(),

                ApprovalDecisionTextColumn::forFlow('issuance_approval', 'invoice_issuance')
                    ->alignment(Alignment::Center)
                    ->toggleable(isToggledHiddenByDefault: true),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([
                CustomerFilter::make('customer'),
                DateRangeFilter::make('issue_date'),
                SellerFilter::make('seller'),
                VoidedFilter::make('voided'),
                StatusFilter::make('status')
                    ->options(InvoiceStatusEnum::class),
            ])
            ->recordActions([
                ViewAction::make()->modal(),
                EditAction::make()
                    ->visible(fn(?Invoice $record) => $record->isElectronicDocumentMutable()),
                SendDocumentEmailAction::make(),
                GeneratePdfAction::make(),
                DeleteAction::make()
                    ->visible(fn(?Invoice $record) => $record->status === InvoiceStatusEnum::Draft),
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
