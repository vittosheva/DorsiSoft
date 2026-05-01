<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Invoices\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class InvoiceCustomerPickerTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Select the customer for the invoice. You can only select customers that have an identification number.'))
            ->columns([
                TextColumn::make('legal_name')->searchable()->sortable(),
                TextColumn::make('trade_name')->searchable()->sortable(),
                TextColumn::make('identification_number')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('phone')->searchable()->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
