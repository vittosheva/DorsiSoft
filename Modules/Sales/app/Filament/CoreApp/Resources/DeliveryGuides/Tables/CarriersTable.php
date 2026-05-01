<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\Tables;

use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;

final class CarriersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('A list of carriers associated with this delivery guide.'))
            ->modifyQueryUsing(fn ($query) => $query->carriers()->with('carrierVehicles:id,business_partner_id,vehicle_plate'))
            ->columns([
                TextColumn::make('legal_name')
                    ->label(__('Legal Name'))
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                TextColumn::make('identification_number')
                    ->label(__('Identification'))
                    ->searchable(),

                TextColumn::make('carrierVehicles.vehicle_plate')
                    ->label(__('Plates'))
                    ->badge()
                    ->separator(','),

                CreatedAtTextColumn::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
