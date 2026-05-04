<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\TaxApplications;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\Finance\Filament\CoreApp\Resources\TaxApplications\Pages\ListTaxApplications;
use Modules\Finance\Filament\CoreApp\Resources\TaxApplications\Tables\TaxApplicationsTable;
use Modules\Finance\Models\TaxApplication;
use UnitEnum;

final class TaxApplicationResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = TaxApplication::class;

    protected static ?string $recordTitleAttribute = 'tax_type';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 95;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'applicable:id,code,establishment_code,emission_point_code,sequential_number',
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public static function table(Table $table): Table
    {
        return TaxApplicationsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxApplications::route('/'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Tax History');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Tax Histories');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Finance');
    }
}
