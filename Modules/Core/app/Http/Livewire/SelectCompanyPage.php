<?php

declare(strict_types=1);

namespace Modules\Core\Http\Livewire;

use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Core\Models\Company;
use ToneGabes\BetterOptions\Forms\Components\RadioList;

#[Layout('filament-panels::components.layout.simple')]
final class SelectCompanyPage extends Component implements HasForms
{
    use InteractsWithForms;

    public ?int $selectACompany = null;

    public function mount(): void
    {
        $user = Auth::user();
        $companies = $user->companies()->select(['id', 'ruc', 'legal_name'])->get();
        $panelId = Filament::getCurrentPanel()?->getId() ?? 'core-app';

        if ($companies->isEmpty()) {
            $this->redirect(Filament::getTenantRegistrationUrl() ?? route('filament.core-app.auth.login'));

            return;
        }

        if ($companies->count() === 1) {
            $company = $companies->first();
            session()->put("filament.tenant.{$panelId}", $company->getKey());
            session()->put('company_explicitly_selected', true);
            $this->redirect(route('filament.core-app.pages.dashboard', ['tenant' => $company->ruc]));
        }
    }

    public function form(Schema $schema): Schema
    {
        $companyIds = Auth::user()
            ->companies()
            ->pluck('legal_name', 'id')
            ->all();

        return $schema
            ->components([
                RadioList::make('selectACompany')
                    ->options($companyIds)
                    ->required()
                    ->rules([
                        'integer',
                        Rule::exists((new Company())->getTable(), 'id')
                            ->where(fn ($query) => $query->whereIn('id', array_keys($companyIds))),
                    ]),
            ]);
    }

    public function submit(): void
    {
        $this->form->validate();

        $companyId = $this->selectACompany;
        $user = Auth::user();
        $panelId = Filament::getCurrentPanel()?->getId() ?? 'core-app';

        /** @var Company|null $company */
        $company = $user->select(['id', 'ruc'])->companies()->whereKey($companyId)->first();

        if ($company === null) {
            $this->addError('selectACompany', __('Invalid company selected.'));

            return;
        }

        session()->put("filament.tenant.{$panelId}", $company->getKey());
        session()->put('company_explicitly_selected', true);

        $this->redirect(route('filament.core-app.pages.dashboard', ['tenant' => $company->ruc]));
    }

    public function render(): View
    {
        return view('core::livewire.select-company');
    }
}
