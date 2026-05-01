<?php

declare(strict_types=1);

namespace Modules\Core\Support\Forms\Selects;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Modules\People\Models\BusinessPartner;

final class IdentificationTypeSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->options([
                'ruc' => __('Tax ID'),
                'cedula' => __('ID Card'),
                'passport' => __('Passport'),
                'other' => __('Other'),
            ])
            ->required()
            ->live()
            ->afterStateUpdatedJs(
                <<<'JS'
                    $set('hideCallout', false);
                JS
            )
            ->afterStateUpdated(function (Set $set, Get $get, ?Model $record): void {
                $set('hideCallout', false);
                $identType = $get('identification_type');
                $identNumber = $get('identification_number');

                if (blank($identType) || blank($identNumber)) {
                    $set('_duplicate_partner_id', null);
                    $set('_duplicate_partner_name', '');

                    return;
                }

                $duplicate = self::findDuplicatePartner($get, $record);
                $set('_duplicate_partner_id', $duplicate?->getKey());
                $set('_duplicate_partner_name', $duplicate?->legal_name ?? '');
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'identification_type';
    }

    private static function findDuplicatePartner(Get $get, ?Model $record): ?BusinessPartner
    {
        $companyId = Filament::getTenant()?->getKey();
        $identType = $get('identification_type');
        $identNumber = $get('identification_number');

        if (blank($identType) || blank($identNumber) || blank($companyId)) {
            return null;
        }

        return BusinessPartner::withTrashed()
            ->select(['id', 'legal_name'])
            ->where('company_id', $companyId)
            ->where('identification_type', $identType)
            ->where('identification_number', $identNumber)
            ->when($record, fn ($q) => $q->whereNot('id', $record->getKey()))
            ->first();
    }
}
