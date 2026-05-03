<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Invoices;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\Pages\CreateInvoice;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\Pages\EditInvoice;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\Pages\ListInvoices;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\Pages\ViewInvoice;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\RelationManagers\CollectionsRelationManager;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\RelationManagers\CreditNotesRelationManager;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\RelationManagers\DebitNotesRelationManager;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\Schemas\InvoiceForm;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\Tables\InvoicesTable;
use Modules\Sales\Models\Invoice;
use UnitEnum;

final class InvoiceResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = Invoice::class;

    protected static ?string $recordTitleAttribute = 'code';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 30;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'documentType:id,code,name',
                'seller:id,name',
                'salesOrder:id,code',
                'allocations:id,invoice_id,amount,collection_id',
                'allocations.collection:id,code,collection_date,collection_method,voided_at',
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return InvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CollectionsRelationManager::class,
            CreditNotesRelationManager::class,
            DebitNotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'edit' => EditInvoice::route('/{record}/edit'),
            'view' => ViewInvoice::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Invoice');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Invoices');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Sales');
    }
}
