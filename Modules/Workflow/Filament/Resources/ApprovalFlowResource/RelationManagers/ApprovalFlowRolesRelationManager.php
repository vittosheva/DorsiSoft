<?php

declare(strict_types=1);

namespace Modules\Workflow\Filament\Resources\ApprovalFlowResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Support\RelationManagers\BaseRelationManager;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Spatie\Permission\Models\Role;

final class ApprovalFlowRolesRelationManager extends BaseRelationManager
{
    protected static string $relationship = 'roles';

    public static function getFormSchema(): array
    {
        $roles = Role::pluck('name', 'id');

        return [
            Select::make('role_id')
                ->options($roles)
                ->searchable()
                ->required()
                ->hint(empty($roles) ? __('No roles configured in the system.') : null),
            TextInput::make('step')
                ->numeric()
                ->minValue(1)
                ->required(),
            Toggle::make('required'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(__('Roles assigned to this approval flow, each representing an actor in the approval chain. The order of roles determines the sequence in which approvals must be completed before a document can advance to the next stage.'))
            ->columns([
                TextColumn::make('role.name')->label(__('Role')),
                TextColumn::make('step')->label(__('Step')),
                TextColumn::make('required')->label(__('Required'))->boolean(),
                CreatedAtTextColumn::make(),
            ]);
    }
}
