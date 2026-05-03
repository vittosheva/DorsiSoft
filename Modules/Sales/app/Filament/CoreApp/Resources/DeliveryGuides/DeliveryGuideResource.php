<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\Pages\CreateDeliveryGuide;
use Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\Pages\EditDeliveryGuide;
use Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\Pages\ListDeliveryGuides;
use Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\Pages\ViewDeliveryGuide;
use Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\Schemas\DeliveryGuideForm;
use Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\Tables\DeliveryGuidesTable;
use Modules\Sales\Models\DeliveryGuide;
use UnitEnum;

final class DeliveryGuideResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = DeliveryGuide::class;

    protected static ?string $recordTitleAttribute = 'code';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?int $navigationSort = 60;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('recipients')
            ->with([
                'documentType:id,code,name',
                'company:id,default_currency_id,logo_pdf_url,legal_name,ruc,phone,tax_address',
                'carrier:id,identification_number,legal_name',
                'recipients' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'delivery_guide_id',
                            'invoice_id',
                            'sort_order',
                            'business_partner_id',
                            'destination_address',
                            'transfer_reason',
                            'route',
                            'destination_establishment_code',
                            'customs_doc',
                            'recipient_name',
                            'recipient_identification',
                            'recipient_identification_type',
                        ])
                        ->with([
                            'items' => function ($query) {
                                $query->select([
                                    'id',
                                    'delivery_guide_recipient_id',
                                    'product_id',
                                    'product_code',
                                    'product_name',
                                    'quantity',
                                    'description',
                                ]);
                            },
                        ]);
                },
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return DeliveryGuideForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliveryGuidesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveryGuides::route('/'),
            'create' => CreateDeliveryGuide::route('/create'),
            'edit' => EditDeliveryGuide::route('/{record}/edit'),
            // 'view' => ViewDeliveryGuide::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Delivery Guide');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Delivery Guides');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Sales');
    }
}
