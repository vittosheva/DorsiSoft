<?php

declare(strict_types=1);

namespace Modules\Core\Filament\CoreApp\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Enums\SubscriptionPlanEnum;
use Modules\Core\Models\Company;
use Modules\Core\Services\EstablishmentSyncService;
use Modules\Core\Services\EstablishmentValidationService;
use Modules\Core\Support\Actions\CancelAction;
use Modules\Core\Support\Forms\TextInputs\RucTextInput;
use Modules\People\Models\User;
use Modules\People\Services\TenantRoleProvisioner;
use Modules\Sri\Enums\SriRegimeTypeEnum;
use Spatie\Permission\Models\Role;
use ToneGabes\BetterOptions\Forms\Components\RadioList;
use ToneGabes\Filament\Icons\Enums\Phosphor;

final class CreateCompany extends RegisterTenant
{
    public static function getLabel(): string
    {
        return __('Register company');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                RucTextInput::make('ruc')
                    ->autofocus()
                    ->uniqueCompanyRuc(),
                TextInput::make('legal_name')
                    ->required()
                    ->maxLength(255),
                Select::make('tax_regime')
                    ->required()
                    ->options(SriRegimeTypeEnum::class)
                    ->enum(SriRegimeTypeEnum::class),
                Textarea::make('business_activity')
                    ->rows(3)
                    ->visibleJs(<<<'JS'
                        $get('ruc') !== null && $get('ruc') !== '' && 
                        $get('tax_regime') !== null && $get('tax_regime') !== ''
                    JS),
                RadioList::make('subscription_plan')
                    ->enum(SubscriptionPlanEnum::class)
                    ->idleIndicator(Phosphor::User->thin())
                    ->required()
                    ->visibleJs(<<<'JS'
                        $get('ruc') !== null && $get('ruc') !== '' && 
                        $get('tax_regime') !== null && $get('tax_regime') !== ''
                    JS),
            ]);
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            CancelAction::make('cancel')
                ->size(Size::Medium)
                ->url($this->getRedirectUrl() ?? $this->getResource()::getUrl('index')),

            $this->getRegisterFormAction(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(array $data): array
    {
        app(EstablishmentValidationService::class)->assertBusinessRules(
            $data['establishments'] ?? []
        );

        return $data;
    }

    protected function handleRegistration(array $data): Company
    {
        $establishmentsData = $data['establishments'] ?? [];
        $subscriptionPlanState = $data['subscription_plan'] ?? SubscriptionPlanEnum::Pro;
        $subscriptionPlan = $subscriptionPlanState instanceof SubscriptionPlanEnum
            ? $subscriptionPlanState
            : SubscriptionPlanEnum::tryFrom((string) $subscriptionPlanState);

        $planCode = $subscriptionPlan?->value ?? SubscriptionPlanEnum::Pro->value;
        $userId = Auth::id();
        $payload = Arr::except($data, ['establishments', 'subscription_plan']);

        return DB::transaction(function () use ($payload, $planCode, $establishmentsData, $userId): Company {
            $company = Company::create($payload);

            $company->subscriptions()->create([
                'plan_code' => $planCode,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'starts_at' => now(),
                'ends_at' => null,
                'metadata' => ['source' => 'tenant_registration'],
            ]);

            if ($establishmentsData !== []) {
                app(EstablishmentSyncService::class)->sync($company, $establishmentsData, 'tenant_registration');
            }

            if ($userId !== null) {
                $company->users()->attach($userId);
            }

            $this->provisionTenantSecurity($company, $userId);

            return $company;
        });
    }

    private function provisionTenantSecurity(Company $company, ?int $userId): void
    {
        app(TenantRoleProvisioner::class)->provisionForCompany((int) $company->getKey());

        if ($userId === null) {
            return;
        }

        $user = User::query()->select(['id', 'name', 'email'])->find($userId);

        if ($user === null) {
            return;
        }

        setPermissionsTeamId((int) $company->getKey());

        try {
            $ownerRole = Role::findOrCreate('Administrador', 'web');
            $company->users()->syncWithoutDetaching([$user->getKey()]);
            $user->syncRoles([$ownerRole->name]);
        } finally {
            setPermissionsTeamId(null);
        }
    }
}
