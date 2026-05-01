<?php

declare(strict_types=1);

namespace Modules\Workflow\Filament\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Traits\HasActiveIcon;
use Modules\People\Enums\RoleEnum;
use Modules\Workflow\Filament\Resources\ApprovalFlowResource\RelationManagers\ApprovalFlowRolesRelationManager;
use Modules\Workflow\Models\ApprovalFlow;

final class ApprovalFlowResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = ApprovalFlow::class;

    public static function getFormSchema(): array
    {
        return [
            Select::make('company_id')
                ->relationship(
                    name: 'company',
                    titleAttribute: 'legal_name',
                    modifyQueryUsing: fn ($query) => $query->select(['id', 'legal_name'])
                )
                ->searchable()
                ->required()
                ->default(Auth::user()?->company_id),
            TextInput::make('key')
                ->required()
                ->maxLength(64)
                ->helperText(__('Unique string identifier used by document models (e.g. invoice_issuance).'))
                ->unique(ignoreRecord: true),
            TextInput::make('name')->required(),
            Select::make('document_type_id')
                ->options(fn () => [])
                ->searchable()
                ->required(),
            Toggle::make('is_active'),
            TextInput::make('min_amount')
                ->numeric()
                ->minValue(0),
        ];
    }

    public static function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label(__('Flow'))
                ->weight(FontWeight::SemiBold),
            TextColumn::make('company.legal_name')
                ->label(__('Company')),
            /* TextColumn::make('documentType.name')
                ->label(__('Document Type')), */
            TextColumn::make('is_active')
                ->boolean(),
            TextColumn::make('min_amount')
                ->label(__('Minimum Amount')),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Multi-tenancy: filtrar por empresa si el usuario no es superadmin
        $query = parent::getEloquentQuery();
        if (! Auth::user()?->hasRole(RoleEnum::SYSTEM_ADMIN->value)) {
            $query->where('company_id', Auth::user()?->company_id);
        }

        return $query;
    }

    public static function rules(): array
    {
        return [
            // Unicidad de flujo por empresa y tipo de documento
            'name' => ['required'],
            'company_id' => ['required', 'exists:core_companies,id'],
            'document_type_id' => ['required'],
            // 'unique:approval_flows,document_type_id,NULL,id,company_id,{company_id}'
        ];
    }

    public static function getRelations(): array
    {
        return [
            ApprovalFlowRolesRelationManager::class,
        ];
    }
}
