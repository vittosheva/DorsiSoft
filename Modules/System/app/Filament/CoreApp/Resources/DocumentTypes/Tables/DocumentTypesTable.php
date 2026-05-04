<?php

declare(strict_types=1);

namespace Modules\System\Filament\CoreApp\Resources\DocumentTypes\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Js;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\IsActiveColumn;

final class DocumentTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                CodeTextColumn::make('code')
                    ->badge(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sri_code')
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->alignment(Alignment::Center),

                IconColumn::make('generates_receivable')
                    ->label(__('GxR'))
                    ->extraHeaderAttributes([
                        'x-tooltip' => Js::from([
                            'content' => __('Generates receivable'),
                            'theme' => '$store.theme',
                            'allowHTML' => false,
                        ])->toHtml(),
                    ])
                    ->boolean()
                    ->alignCenter(),

                IconColumn::make('generates_payable')
                    ->label(__('GxP'))
                    ->extraHeaderAttributes([
                        'x-tooltip' => Js::from([
                            'content' => __('Generates payable'),
                            'theme' => '$store.theme',
                            'allowHTML' => false,
                        ])->toHtml(),
                    ])
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('series_count')
                    ->badge()
                    ->color('success')
                    ->alignment(Alignment::Center),

                IconColumn::make('affects_accounting')
                    ->boolean()
                    ->alignCenter(),

                IconColumn::make('is_electronic')
                    ->boolean()
                    ->alignCenter(),

                IsActiveColumn::make('is_active')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->filters([
                TernaryFilter::make('affects_accounting'),
                TernaryFilter::make('is_electronic'),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modal(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
