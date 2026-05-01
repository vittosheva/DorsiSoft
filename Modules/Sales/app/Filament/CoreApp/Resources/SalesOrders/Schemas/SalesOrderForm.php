<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SalesOrders\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Support\Components\Sections\AuditSection;
use Modules\Core\Support\Forms\DatePickers\IssueDatePicker;
use Modules\Core\Support\Forms\Selects\CurrencyCodeSelect;
use Modules\Core\Support\Forms\Textareas\NotesTextarea;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\People\Support\Forms\Selects\CustomerBusinessPartnerSelect;
use Modules\People\Support\Forms\Selects\SellerUserSelect;
use Modules\Sales\Livewire\SalesOrderItems;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Support\Forms\Components\DocumentStatusBadge;

final class SalesOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->schema([
                        self::orderDataSection()
                            ->columnSpan(5),
                        self::customerSection()
                            ->columnSpan(7),
                    ]),

                Livewire::make(SalesOrderItems::class, fn (?SalesOrder $record, string $operation) => [
                    'orderId' => $record?->getKey(),
                    'minimumItemsCount' => 1,
                    'minimumItemsValidationMessage' => __('Add at least one item to the document.'),
                    'operation' => $operation,
                ]),

                TextInput::make('document_items_count')
                    ->hiddenLabel()
                    ->readOnly()
                    ->dehydrated(false)
                    ->rules(['integer', 'min:1'])
                    ->validationMessages(['min' => __('Add at least one item to the document.')])
                    ->extraInputAttributes(['class' => 'sr-only'])
                    ->extraAttributes(['class' => 'hidden has-[.fi-fo-field-wrp-error-message]:block']),

                Grid::make(12)
                    ->schema([
                        self::notesSection()
                            ->columnSpan(7),

                        Grid::make(1)
                            ->schema([
                                AuditSection::make(),
                            ])
                            ->columnSpan(5),
                    ]),
            ])
            ->columns(1);
    }

    private static function orderDataSection(): Section
    {
        return Section::make(__('Order Data'))
            ->icon(Heroicon::DocumentText)
            ->afterHeader(DocumentStatusBadge::make())
            ->schema([
                CodeTextInput::make('code')
                    ->autoGenerateFromModel(
                        scope: fn () => [
                            'company_id' => Filament::getTenant()?->getKey(),
                        ],
                    )
                    ->columnSpan(4),

                IssueDatePicker::make('issue_date')
                    ->columnSpan(4),

                CurrencyCodeSelect::make('currency_code')
                    ->columnSpan(5)
                    ->hidden(),
            ])
            ->columns(12);
    }

    private static function customerSection(): Section
    {
        return Section::make(__('Customer'))
            ->icon(Heroicon::User)
            ->schema([
                CustomerBusinessPartnerSelect::make('business_partner_id')
                    ->required()
                    ->columnSpan(8),

                SellerUserSelect::make('seller_id')
                    ->columnSpan(4),
            ])
            ->columns(12);
    }

    private static function notesSection(): Section
    {
        return Section::make(__('Internal Notes'))
            ->icon(Heroicon::ChatBubbleBottomCenterText)
            ->schema([
                NotesTextarea::make('notes')
                    ->columnSpanFull(),
            ])
            ->columnSpan(8);
    }
}
